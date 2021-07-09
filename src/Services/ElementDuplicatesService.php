<?php

namespace App\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use App\Services\ElementActionService;
use App\Document\ElementStatus;
use App\Document\ModerationState;

class ElementDuplicatesService
{
    // To know if an element have already been processed, we use the moderationstate : PotentialDuplicate
    // but as we are processing element in batch, during the same batch data is not persisted and so instead
    // we use this array or element Id that we exclude from the detection
    protected $duplicatesFound = [];

    protected $dupConfig = null;

    public function __construct(DocumentManager $dm, ElementActionService $elementActionService)
    {
        $this->dm = $dm;
        $this->elementActionService = $elementActionService;
    }

    private function getDupConfig()
    {
        if ($this->dupConfig === null)
            $this->dupConfig = $this->dm->get('Configuration')->findConfiguration()->getDuplicates();
        return $this->dupConfig;
    }

    public function detectDuplicatesFor($element, $restrictToSources = [])
    {       
        if ($element->getStatus() >= ElementStatus::PendingModification
        && !in_array($element->getId(), $this->duplicatesFound)
        && !$element->isPotentialDuplicate()) {
            $duplicates = $this->dm->get('Element')->findDuplicatesFor($element, $this->duplicatesFound, $restrictToSources);
            if (count($duplicates) == 0) return null;
            // only keep two duplicates, so get easier to manage for the users (less complicated cases)
            // so we sort duplicates and keep first (best choice)
            usort($duplicates, function($a, $b) use ($element) {
                $aIsValid = $a->isValid();
                $bIsValid = $b->isValid();
                if ($aIsValid != $bIsValid) return $bIsValid;    
                $aIsPerfectMatch = $this->isPerfectMatch($element, $a);
                $bIsPerfectMatch = $this->isPerfectMatch($element, $b);
                if ($aIsPerfectMatch != $bIsPerfectMatch) return $bIsPerfectMatch;                            
                if ($a->getScore() != null && $b->getScore() != null) return $a->getScore() < $b->getScore();
            }); 
            $bestDuplicate = array_shift($duplicates);
            $isPerfectMatch = $this->isPerfectMatch($element, $bestDuplicate);
            $duplicatesToProceed = [$element, $bestDuplicate];
            
            // Choose which duplicate to keep
            $sourcePriorities = $this->getDupConfig()->getSourcePriorityInAutomaticMerge();
            usort($duplicatesToProceed, function($a, $b) use ($sourcePriorities) {
                $aPriority = array_search($a->getSourceKey(), $sourcePriorities);
                $bPriority = array_search($b->getSourceKey(), $sourcePriorities);
                if ($aPriority != $bPriority) {
                    // order by source priority
                    return $aPriority > $bPriority;
                } else {
                    // Or get the more recent
                    try { $diffDays = (float) date_diff($a->getUpdatedAt(), $b->getUpdatedAt())->format('%d'); }
                    catch (\Exception $e) { return 0; }                    
                    return $diffDays;
                };
            });   

            // Remember them so we do not check duplicate on them again
            foreach ($duplicatesToProceed as $duplicate) {
                $this->duplicatesFound[] = $duplicate->getId();
                $duplicate->setModerationState(ModerationState::PotentialDuplicate);
            }
            
            $elementToKeep = array_shift($duplicatesToProceed);
            $duplicate = array_pop($duplicatesToProceed);
            $autoMerge = $isPerfectMatch && $this->getDupConfig()->getAutomaticMergeIfPerfectMatch();
            if ($autoMerge) {
                $elementToKeep = $this->automaticMerge($elementToKeep, [$duplicate]);
            } else {
                $elementToKeep->setIsDuplicateNode(true);
                $elementToKeep->addPotentialDuplicate($duplicate);
                $duplicate->setModerationState(ModerationState::PotentialDuplicate);
            }
            return [
                'automaticMerge' => $autoMerge,
                'elementToKeep' => $elementToKeep,
                'duplicate' => $duplicate
            ];
        }
    }

    private function isPerfectMatch($element, $duplicate)
    {
        if ($this->getDupConfig()->getUseGlobalSearch()
            && slugify($duplicate->getName()) == slugify($element->getName()))
            return true;

        foreach($this->getDupConfig()->getFieldsToBeUsedForComparaison() as $field) {
            if ($element->getProperty($field) && $duplicate->getProperty($field) == $element->getProperty($field))
                return true;
        }

        return false;
    }

    public function automaticMerge($merged, $duplicates)
    {
        $mergedData = $merged->getData();
        $mergedOptionIds = $merged->getOptionIds();

        foreach ($duplicates as $duplicate) {
            // Auto merge option values
            foreach ($duplicate->getOptionValues() as $dupOptionValue) {
                if (!in_array($dupOptionValue->getOptionId(), $mergedOptionIds)) {
                    $mergedOptionIds[] = $dupOptionValue->getOptionId();
                    $merged->addOptionValue($dupOptionValue);
                }
            }
            // Auto merge custom attributes
            foreach ($duplicate->getData() as $key => $value) {
                if ($value && (!array_key_exists($key, $mergedData) || !$mergedData[$key])) {
                    $mergedData[$key] = $value;
                }
            }
            // merge non duplicates
            foreach ($duplicate->getNonDuplicates() as $nonDup) $merged->addNonDuplicate($nonDup);
            // Auto merge special attributes
            $merged->setImages(array_merge($merged->getImagesArray(), $duplicate->getImagesArray()));
            $merged->setFiles(array_merge($merged->getFilesArray(), $duplicate->getFilesArray()));
            if (!$merged->getOpenHours() && $duplicate->getOpenHours()) {
                $merged->setOpenHours($duplicate->getOpenHours());
            }
            if (!$merged->getUserOwnerEmail() && $duplicate->getUserOwnerEmail()) {
                $merged->setUserOwnerEmail($duplicate->getUserOwnerEmail());
            }
            if (!$merged->getAddress()->isComplete()) {
                $address = $merged->getAddress();
                $dupAddress = $duplicate->getAddress();
                if (!$address->getStreetAddress() && $dupAddress->getStreetAddress()) {
                    $address->setStreetAddress($dupAddress->getStreetAddress());
                }
                if (!$address->getAddressLocality() && $dupAddress->getAddressLocality()) {
                    $address->setAddressLocality($dupAddress->getAddressLocality());
                }
                if (!$address->getAddressCountry() && $dupAddress->getAddressCountry()) {
                    $address->setAddressCountry($dupAddress->getAddressCountry());
                }
                if (!$address->getPostalCode() && $dupAddress->getPostalCode()) {
                    $address->setPostalCode($dupAddress->getPostalCode());
                }
                $merged->setAddress($address);
            }
            $duplicate->setDuplicateOf($merged->getId());
            // Merge status. If one of the duplicate is deleted, then the merged one will be deleted as well
            // If merged one is pending, and duplicate is validated, then merged will be validated
            if (!$merged->isDeleted() && ($duplicate->getStatus() > $merged->getStatus() || $duplicate->isDeleted())) 
                $merged->setStatus($duplicate->getStatus());
            $this->elementActionService->delete($duplicate, false);
        }
        $merged->setModerationState(ModerationState::NotNeeded);
        $merged->setData($mergedData);

        return $merged;
    }
}