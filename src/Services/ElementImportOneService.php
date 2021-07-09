<?php

namespace App\Services;

use App\Document\Coordinates;
use App\Document\Element;
use App\Document\ElementFile;
use App\Document\ElementImage;
use App\Document\ElementStatus;
use App\Document\ModerationState;
use App\Document\OpenHours;
use App\Document\OptionValue;
use App\Document\PostalAddress;
use Doctrine\ODM\MongoDB\DocumentManager;
use Geocoder\ProviderAggregator;

class ElementImportOneService
{
    private $dm;
    private $geocoder;
    private $interactionService;
    private $elementService;
    protected $optionIdsToAddToEachElement = [];

    protected $mainConfigHaveChangedSinceLastImport;
    /**
     * Constructor.
     */
    public function __construct(DocumentManager $dm, ProviderAggregator $geocoder,
                                UserInteractionService $interactionService,
                                ElementPendingService $elementService)
    {
        $this->dm = $dm;
        $this->geocoder = $geocoder->using('mapbox');
        $this->interactionService = $interactionService;
        $this->elementService = $elementService;
        $this->currentRow = [];
    }

    public function initialize($import)
    {
        $this->optionIdsToAddToEachElement = [];
        foreach ($import->getOptionsToAddToEachElement() as $option) {
            $this->optionIdsToAddToEachElement[] = $option->getId();
            $this->dm->persist($option->getParent()); // Strange bug, need to manually persist parent
        }

        $this->mainConfigHaveChangedSinceLastImport = $import->getMainConfigUpdatedAt() > $import->getLastRefresh();
    }

    public function createElementFromArray($row, $import)
    {
        $updateExisting = false; // if we create a new element or update an existing one

        // adds missings fields instead of checking if each field is set before accessing
        $missingFields = array_diff(Element::CORE_FIELDS, array_keys($row));
        foreach ($missingFields as $missingField) {
            $row[$missingField] = ('categories' == $missingField) ? [] : '';
        }

        $element = null;

        if ($row['id']) {
            if (in_array($row['id'], $import->getIdsToIgnore())) {
                return $this->resultData($element, 'ignored');
            }
            $qb = $this->dm->query('Element');
            $qb->field('source')->references($import);
            $qb->field('oldId')->equals(''.$row['id']);
            $qb->field('status')->notEqual(ElementStatus::ModifiedPendingVersion);
            $element = $qb->getQuery()->getSingleResult();
        } elseif (is_string($row['name']) && strlen($row['name']) > 0) {
            $qb = $this->dm->query('Element');
            $qb->field('source')->references($import);
            $qb->field('status')->notEqual(ElementStatus::ModifiedPendingVersion);
            $qb->field('name')->equals($row['name']);

            if (is_string($row['latitude']) && strlen($row['latitude']) > 0 && is_string($row['longitude']) && strlen($row['longitude']) > 0) {
                $geo = new Coordinates($row['latitude'], $row['longitude']);
                $qb->field('geo.latitude')->equals($geo->getLatitude());
                $qb->field('geo.longitude')->equals($geo->getLongitude());
                $element = $qb->getQuery()->getSingleResult();
            } elseif (strlen($row['streetAddress']) > 0) {
                if (strlen($row['streetAddress']) > 0) {
                    $qb->field('address.streetAddress')->equals($row['streetAddress']);
                }
                if (strlen($row['addressLocality']) > 0) {
                    $qb->field('address.addressLocality')->equals($row['addressLocality']);
                }
                if (strlen($row['postalCode']) > 0) {
                    $qb->field('address.postalCode')->equals($row['postalCode']);
                }
                $element = $qb->getQuery()->getSingleResult();
            }
        }

        if ($element) { // if the element already exists, we update it
            $updateExisting = true;
            // if main import config has change, we should reimport anyway
            if (!$this->mainConfigHaveChangedSinceLastImport) {
                $updatedAtField = $import->getFieldToCheckElementHaveBeenUpdated();
                // if updatedAtField hasn't change, nothing to do
                if ($updatedAtField && array_key_exists($updatedAtField, $row)) {
                    if ($row[$updatedAtField] && $row[$updatedAtField] == $element->getCustomProperty($updatedAtField)) {
                        $element->setPreventJsonUpdate(true);
                        $this->dm->persist($element);
                        return $this->resultData($element, 'nothing_to_do');
                    } else {
                        $somethingHasChanged = true;
                    }
                }
            }

            // resetting "geoloc" and "no options" modearation state so it will be calculated again
            if ($element->getModerationState() < 0) {
                $element->setModerationState(ModerationState::NotNeeded);
            }
        } else {
            $element = new Element();
            $element->setStatus(ElementStatus::Imported);
        }
        $this->currentRow = $row;

        $this->createOptionValues($element, $row, $import);
        if ($import->getPreventImportIfNoCategories() && ModerationState::NoOptionProvided == $element->getModerationState()) {
            return $this->resultData($element, 'no_category');
        }

        $element->setOldId($row['id']);
        $element->setName($row['name']);
        $oldFormatedAdress = $element->getFormatedAddress();
        $newAddress = new PostalAddress($row['streetAddress'], $row['addressLocality'], $row['postalCode'], $row['addressCountry'], $row['customFormatedAddress']);
        $element->setAddress($newAddress);

        $element->setSource($import);
        // Override sourceKey for standard import
        if (!$import->isDynamicImport() && (strlen($row['source']) > 0 && 'Inconnu' != $row['source']))
            $element->setSourceKey($row['source']);


        if (array_key_exists('owner', $row)) {
            $element->setUserOwnerEmail($row['owner']);
        }
        if (array_key_exists('email', $row)) {
            $element->setEmail($row['email']);
        }

        $lat = $row['latitude'];
        $lng = $row['longitude'];
        try {
            if (is_object($lat) || is_array($lat) || 0 == strlen($lat) || is_object($lng) || 0 == strlen($lng) || 'null' == $lat || null == $lat) {
                $lat = 0; $lng = 0;  
                $newFormatedAddress = $newAddress->getFormatedAddress();   
                // Geocode if necessary  
                if ($newFormatedAddress == $oldFormatedAdress) {
                    $lat = $element->getGeo()->getLatitude();
                    $lng = $element->getGeo()->getLongitude();
                } 
                if ($lat == 0 && $lng == 0 && $import->getGeocodeIfNecessary() && $newFormatedAddress && strlen($newFormatedAddress) > 0) {
                    $result = $this->geocoder->geocode($newFormatedAddress)->first()->getCoordinates();
                    $lat = $result->getLatitude();
                    $lng = $result->getLongitude();
                }
            }
        } catch (\Exception $e) {}
        
        if (0 == $lat || 0 == $lng) {
            $element->setModerationState(ModerationState::GeolocError);
        }
        
        $element->setGeo(new Coordinates($lat, $lng));
        $this->createImages($element, $row);
        $this->createFiles($element, $row);
        $this->createOpenHours($element, $row);
        unset($row['osm_opening_hours']);
        $this->saveCustomFields($element, $row);

        if ($updateExisting) {
            $somethingHasChanged = $somethingHasChanged ?? $this->checkElementHaveChanged($element);
            if (!$somethingHasChanged) {
                $element->setPreventJsonUpdate(true);
                $this->dm->persist($element);
                return $this->resultData($element, 'nothing_to_do');
            }
        }

        if ($import->getModerateElements()) {
            if ($element->isPendingAdd()) {
                // do nothing, we wait for the element to be validated
            } else {
                if ($updateExisting) {
                    $element = $this->elementService->savePendingModification($element);
                    $this->elementService->createPending($element, true, null, true);
                } else {
                    $this->elementService->createPending($element, false, null, true);
                }
            }
        } else {
            if ($updateExisting) {
                // edit, do not create contribution
            } else {
                // create import contribution if first time imported
                $this->interactionService->createContribution($element, null, 4, $element->getStatus(), null, null, true);
            }
        }

        $element->updateTimestamp();
        $element->setPreventLinksUpdate(true); // Check the links between elements all at once at the end of the import

        $this->dm->persist($element);

        return $this->resultData($element, $updateExisting ? 'updated' : 'created');
    }

    private function resultData($element, $state)
    {
        $result = ['status' => $state];
        if ($element) {
            $result['id'] = $element->getId();
            if ($element->isPendingModification()){
                $result['id_pending_modified'] = $element->getModifiedElement()->getId();
            }
        }
        return $result;
    }

    private function saveCustomFields($element, $raw_data)
    {
        $customFields = array_diff(array_keys($raw_data), Element::CORE_FIELDS);
        $customData = [];
        foreach ($customFields as $customField) {
            if ($customField && is_string($customField)) {
                $customData[$customField] = $raw_data[$customField];
            }
        }
        $element->setCustomData($customData);
    }

    private function createImages($element, $row)
    {
        $element->resetImages();
        $images_raw = $row['images'];
        if (is_string($images_raw) && strlen($images_raw) > 0) {
            $images = explode(',', $row['images']);
        } elseif (is_array($images_raw)) {
            $images = $images_raw;
        } else {
            $keys = array_keys($row);
            $image_keys = array_filter($keys, function ($key) { return startsWith($key, 'image'); });
            $images = array_map(function ($key) use ($row) { return $row[$key]; }, $image_keys);
        }

        if (0 == count($images)) {
            return;
        }

        foreach ($images as $imageUrl) {
            if (is_string($imageUrl) && strlen($imageUrl) > 5) {
                $elementImage = new ElementImage();
                $elementImage->setExternalImageUrl($imageUrl);
                $element->addImage($elementImage);
            }
        }
    }

    private function createFiles($element, $row)
    {
        $element->resetFiles();
        $files = $row['files'];
        if (is_string($files) && strlen($files) > 0) {
            $files = explode(',', $files);
        }
        if (!is_array($files) || 0 == count($files)) {
            return;
        }
        foreach ($files as $url) {
            if (is_string($url) && strlen($url) > 5) {
                $elementFile = new ElementFile();
                $elementFile->setFileUrl($url);
                $name = explode('/', $url);
                $name = end($name);
                $elementFile->setFileName($name);
                $element->addFile($elementFile);
            }
        }
    }

    private function createOpenHours($element, $row)
    {
        if(isset($row['osm_opening_hours'])) {
            try {
                $oh = new OpenHours();
                $oh->buildFromOsm($row['osm_opening_hours']);
                $element->setOpenHours($oh);                
            }
            catch(\Exception $e) {;}
        }
        else if(isset($row['openHours']) && is_associative_array($row['openHours'])) {
            $element->setOpenHours(new OpenHours($row['openHours']));
        }
    }

    private function createOptionValues($element, $row, $import)
    {
        if (!$import->isHandlingCategories()) return;
        $element->resetOptionsValues();
        $optionsIdAdded = [];
        $defaultOption = ['index' => 0, 'description' => ''];
        foreach ($row['categories'] as $option) {
            $option = array_merge($defaultOption, $option);
            $this->addOptionValue($element, $option['id'], $option['index'], $option['description']);
        }

        if ($import->getNeedToHaveOptionsOtherThanTheOnesAddedToEachElements()) {
            // checking option number before adding optionIdsToAddToEachElement
            if (0 == count($element->getOptionValues())) {
                $element->setModerationState(ModerationState::NoOptionProvided);
            }
        }
        // Manually add some options to each element imported
        foreach ($this->optionIdsToAddToEachElement as $optionId) {
            if (!in_array($optionId, $optionsIdAdded)) {
                $optionsIdAdded[] = $this->addOptionValue($element, $optionId);
            }
        }
        if (0 == count($element->getOptionValues())) {
            $element->setModerationState(ModerationState::NoOptionProvided);
        }
    }

    private function addOptionValue($element, $id, $index = 0, $description = '')
    {
        if (!$id || '0' == $id || 0 == $id) {
            return;
        }
        $optionValue = new OptionValue();
        $optionValue->setOptionId($id);
        $optionValue->setIndex($index);
        $optionValue->setDescription($description);
        $element->addOptionValue($optionValue);

        return $id;
    }

    // compare old and new element to see if something has changed
    private function checkElementHaveChanged($element)
    {
        $changeset = $this->dm->getChangeSet($element);
        $changeset = array_filter($changeset, function($e) {
            $a = method_exists($e[0], 'toArray') ? $e[0]->toArray() : $e[0];
            $b = method_exists($e[1], 'toArray') ? $e[1]->toArray() : $e[1];
            return $a != $b;
        });
        return count($changeset);
    }
}
