<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique;

abstract class ImportState
{
    const Started = 'started';
    const Downloading = 'downloading';
    const InProgress = 'in_progress';
    const Completed = 'completed';
    const Errors = 'errors';
    const Failed = 'failed';
}

/**
 * @MongoDB\Document
 * @Vich\Uploadable
 * Import data into GoGoCarto. the data can imported through a static file, or via API url
 * The Import can be made once for all (static import) or dynamically every X days (ImportDynamic)
 *
 * @MongoDB\InheritanceType("SINGLE_COLLECTION")
 * @MongoDB\DiscriminatorField("type")
 * @MongoDB\DiscriminatorMap({"normal"="Import", "dynamic"="ImportDynamic"})
 * @Unique(fields="sourceName")
 */
class Import extends AbstractFile
{
    protected $vichUploadFileKey = 'import_file';

    /**
     * @var int
     * @MongoDB\Id(strategy="INCREMENT")
     */
    private $id;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    public $sourceName;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    public $sourceType;

    /**
     * @var string Url of API to get the data
     * @MongoDB\Field(type="string")
     */
    public $url;

    /**
     * @MongoDB\ReferenceMany(targetDocument="App\Document\Option", cascade={"persist"})
     */
    private $optionsToAddToEachElement = [];

    /**
     * @MongoDB\Field(type="bool")
     */
    private $needToHaveOptionsOtherThanTheOnesAddedToEachElements = false;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $preventImportIfNoCategories = false;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    public $fieldToCheckElementHaveBeenUpdated;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $geocodeIfNecessary = false;

    /**
     * @MongoDB\ReferenceMany(targetDocument="App\Document\GoGoLog", cascade={"all"})
     */
    private $logs;

    /**
     * State of the import when processing. Type of ImportState
     * When processing import, this variable is being updated in realtime, so the client can follow
     * the state of the import also in realtime.
     *
     * @MongoDB\Field(type="string")
     */
    private $currState;

    /**
     * A message can be added to the state information.
     *
     * @MongoDB\Field(type="string")
     */
    private $currMessage;

    /**
     * After importing some Data, if the user hard delete some elements, their ids will be remembered
     * so next time we do not import them again.
     *
     * @MongoDB\Field(type="collection")
     */
    private $idsToIgnore = [];

    /**
     * @MongoDB\Field(type="hash")
     */
    private $ontologyMapping = [];

    /**
     * @MongoDB\Field(type="bool")
     */
    private $newOntologyToMap = false;

    /**
     * @MongoDB\Field(type="hash")
     */
    private $taxonomyMapping = [];

    /**
     * @MongoDB\Field(type="bool")
     */
    private $newTaxonomyToMap = false;

    /**
     * Custom code made by the user to be run on the $data object when importing.
     *
     * @MongoDB\Field(type="string")
     */
    private $customCode = '<?php';

    /**
     * Custom code made by the user to be run on each $element when exporting.
     * This Is used in the cas of an OSM export for example, when we convert back
     * The gogocarto data into an osm feature
     *
     * @MongoDB\Field(type="string")
     */
    private $customCodeForExport = '<?php';

    /**
     * @var date
     *
     * @MongoDB\Field(type="date")
     */
    private $lastRefresh = null;

    /**
     * @MongoDB\Field(type="date")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @MongoDB\Field(type="date")
     * @Gedmo\Timestampable(on="change", field={"customCode", "ontologyMapping", "optionsToAddToEachElement", "url", "sourceType", "file", "osmQueriesJson"})
     */
    private $mainConfigUpdatedAt;

    /**
     * @MongoDB\Field(type="bool")
     */
    private $moderateElements = false;

    public $warnUserThatDuplicatesWillBeDetectedAndAutoMerged = false;

    public function __construct()
    {
        $this->logs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return 'Import '.$this->sourceName;
    }

    public function isDynamicImport()
    {
        return false;
    }

    public function addIdToIgnore($id)
    {
        $this->idsToIgnore[] = $id;
    }

    public function isCategoriesFieldMapped()
    {
        return $this->getOntologyMapping() ? in_array('categories', $this->getMappedProperties()) : false;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        $codeFields = ['customCode' => $this->customCode, 'customCodeForExport' => $this->customCodeForExport];
        foreach($codeFields as $field => $value) {
            if (preg_match('/new |process|mongo|this|symfony|exec|passthru|shell_exec|system|proc_open|popen|curl_exec|curl_multi_exec|parse_ini_file|show_source|var_dump|print_r/i', $value)) {
                $context->buildViolation($this->t('error.edit_constraints'))
                    ->atPath($field)
                    ->addViolation();
            }
        }
        
    }

    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;
        return $this;
    }
    public function getSourceType()
    {
        if (isset($this->sourceType)) return $this->sourceType;
        if (isset($this->osmQueriesJson)) return 'openstreetmap';
        if ($this->url) return 'json';
        if ($this->file) return 'csv';
    }

    public function isHandlingCategories()
    {
        return count($this->taxonomyMapping) > 0 || count($this->optionsToAddToEachElement) > 0;
    }

    /**
     * Get id.
     *
     * @return int_id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set geocodeIfNecessary.
     *
     * @param bool $geocodeIfNecessary
     *
     * @return $this
     */
    public function setGeocodeIfNecessary($geocodeIfNecessary)
    {
        $this->geocodeIfNecessary = $geocodeIfNecessary;

        return $this;
    }

    /**
     * Get geocodeIfNecessary.
     *
     * @return bool $geocodeIfNecessary
     */
    public function getGeocodeIfNecessary()
    {
        if ($this->getSourceType() == 'osm') return false;
        return $this->geocodeIfNecessary;
    }

    /**
     * Add optionsToAddToEachElement.
     *
     * @param App\Document\Option $optionsToAddToEachElement
     */
    public function addOptionsToAddToEachElement(\App\Document\Option $optionsToAddToEachElement)
    {
        $this->optionsToAddToEachElement[] = $optionsToAddToEachElement;
    }

    /**
     * Remove optionsToAddToEachElement.
     *
     * @param App\Document\Option $optionsToAddToEachElement
     */
    public function removeOptionsToAddToEachElement(\App\Document\Option $optionsToAddToEachElement)
    {
        $this->optionsToAddToEachElement->removeElement($optionsToAddToEachElement);
    }

    /**
     * Get optionsToAddToEachElement.
     *
     * @return \Doctrine\Common\Collections\Collection $optionsToAddToEachElement
     */
    public function getOptionsToAddToEachElement()
    {
        return $this->optionsToAddToEachElement;
    }

    /**
     * Set sourceName.
     *
     * @param string $sourceName
     *
     * @return $this
     */
    public function setSourceName($sourceName)
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    /**
     * Get sourceName.
     *
     * @return string $sourceName
     */
    public function getSourceName()
    {
        return $this->sourceName;
    }

    /**
     * Add log.
     *
     * @param App\Document\GoGoLog $log
     */
    public function addLog(\App\Document\GoGoLog $log)
    {
        $this->logs[] = $log;
    }

    /**
     * Remove log.
     *
     * @param App\Document\GoGoLog $log
     */
    public function removeLog(\App\Document\GoGoLog $log)
    {
        $this->logs->removeElement($log);
    }

    /**
     * Get logs.
     *
     * @return \Doctrine\Common\Collections\Collection $logs
     */
    public function getLogs()
    {
        $logs = is_array($this->logs) ? $this->logs : $this->logs->toArray();
        usort($logs, function ($a, $b) { return $b->getCreatedAt()->getTimestamp() - $a->getCreatedAt()->getTimestamp(); });

        return $logs;
    }

    /**
     * Set currState.
     *
     * @param string $currState
     *
     * @return $this
     */
    public function setCurrState($currState)
    {
        $this->currState = $currState;

        return $this;
    }

    /**
     * Get currState.
     *
     * @return string $currState
     */
    public function getCurrState()
    {
        return $this->currState;
    }

    /**
     * Set currMessage.
     *
     * @param string $currMessage
     *
     * @return $this
     */
    public function setCurrMessage($currMessage)
    {
        $this->currMessage = $currMessage;

        return $this;
    }

    /**
     * Get currMessage.
     *
     * @return string $currMessage
     */
    public function getCurrMessage()
    {
        return $this->currMessage;
    }

    /**
     * Set idsToIgnore.
     *
     * @param collection $idsToIgnore
     *
     * @return $this
     */
    public function setIdsToIgnore($idsToIgnore)
    {
        $this->idsToIgnore = $idsToIgnore;

        return $this;
    }

    /**
     * Get idsToIgnore.
     *
     * @return collection $idsToIgnore
     */
    public function getIdsToIgnore()
    {
        return $this->idsToIgnore;
    }

    /**
     * Set needToHaveOptionsOtherThanTheOnesAddedToEachElements.
     *
     * @param bool $needToHaveOptionsOtherThanTheOnesAddedToEachElements
     *
     * @return $this
     */
    public function setNeedToHaveOptionsOtherThanTheOnesAddedToEachElements($needToHaveOptionsOtherThanTheOnesAddedToEachElements)
    {
        $this->needToHaveOptionsOtherThanTheOnesAddedToEachElements = $needToHaveOptionsOtherThanTheOnesAddedToEachElements;

        return $this;
    }

    /**
     * Get needToHaveOptionsOtherThanTheOnesAddedToEachElements.
     *
     * @return bool $needToHaveOptionsOtherThanTheOnesAddedToEachElements
     */
    public function getNeedToHaveOptionsOtherThanTheOnesAddedToEachElements()
    {
        return $this->needToHaveOptionsOtherThanTheOnesAddedToEachElements;
    }

    /**
     * Set fieldToCheckElementHaveBeenUpdated.
     *
     * @param string $fieldToCheckElementHaveBeenUpdated
     *
     * @return $this
     */
    public function setFieldToCheckElementHaveBeenUpdated($fieldToCheckElementHaveBeenUpdated)
    {
        $this->fieldToCheckElementHaveBeenUpdated = $fieldToCheckElementHaveBeenUpdated;

        return $this;
    }

    /**
     * Get fieldToCheckElementHaveBeenUpdated.
     *
     * @return string $fieldToCheckElementHaveBeenUpdated
     */
    public function getFieldToCheckElementHaveBeenUpdated()
    {
        if ($this->getSourceType() == 'osm') return 'osm_version';
        return $this->fieldToCheckElementHaveBeenUpdated ?? 'updateAt';
    }

    /**
     * Set ontologyMapping.
     *
     * @param array $ontologyMapping
     *
     * @return $this
     */
    public function setOntologyMapping($ontologyMapping)
    {
        if ($ontologyMapping == null) return;
        foreach($ontologyMapping as $key => $mappedObject) { 
            if ($key && strlen($key) > 0) { // Mongo does not authorize saving empty key
                if (is_string($mappedObject)) {
                    $ontologyMapping[$key] = array_merge($this->ontologyMapping[$key], ['mappedProperty' => $mappedObject]);
                } else {
                    $ontologyMapping[$key]['mappedProperty'] = slugify($mappedObject['mappedProperty'], false);
                }
            }
        }
        $this->ontologyMapping = $ontologyMapping;
        return $this;
    }

    /**
     * Get ontologyMapping.
     * @return array ontologyMapping
     */
    public function getOntologyMapping()
    {
        // backward compatibility we used to save mappedObject as a string
        foreach($this->ontologyMapping as &$mappedObject) {           
            if (is_string($mappedObject)) {
                $mappedObject = [ 
                    'mappedProperty' => $mappedObject,
                    'collectedCount' => 0,
                    'collectedValues' => [],
                    'collectedPercent' => null
                ];
            }
        }
        ksort($this->ontologyMapping);
        uasort($this->ontologyMapping, function($a,$b) {
            return $a['collectedCount'] < $b['collectedCount'];
        });
        
        return $this->ontologyMapping;
    }

    public function getMappedProperties()
    {
        return array_map(function($a) {
            return $a['mappedProperty'];
        }, $this->getOntologyMapping());
    }

    /**
     * Set taxonomyMapping.
     *
     * @param hash $taxonomyMapping
     *
     * @return $this
     */
    public function setTaxonomyMapping($taxonomyMapping)
    {
        if ($taxonomyMapping == null) return;
        $newMapping = [];
        foreach($taxonomyMapping as $key => $mappedObject) { 
            if ($key && strlen($key) > 0) {
                if (!is_associative_array($mappedObject)) {
                    $newMapping[$key] = array_merge($this->taxonomyMapping[$key], ['mappedCategoryIds' => $mappedObject]);
                } else {
                    $newMapping[$key] = $mappedObject;
                }
            }            
        }
        $this->taxonomyMapping = $newMapping;

        return $this;
    }

    /**
     * Get taxonomyMapping.
     *
     * @return array $taxonomyMapping
     */
    public function getTaxonomyMapping()
    {
        // backward compatibility we used to save mappedObject as a simple categories Ids array
        foreach($this->taxonomyMapping as &$mappedObject) {           
            if (!is_associative_array($mappedObject)) {
                $mappedObject = [ 
                    'mappedCategoryIds' => $mappedObject,
                    'fieldName' => '',
                    'collectedCount' => 0,                    
                    'collectedPercent' => null
                ];
            }
            $mappedObject['mappedCategoryIds'] = array_filter($mappedObject['mappedCategoryIds']);
        }
        ksort($this->taxonomyMapping);
        uasort($this->taxonomyMapping, function($a,$b) {
            return $a['fieldName'] < $b['fieldName'];
        });
        return $this->taxonomyMapping;
    }

    /**
     * Set customCode.
     *
     * @param string $customCode
     *
     * @return $this
     */
    public function setCustomCode($customCode)
    {
        $this->customCode = $customCode;

        return $this;
    }

    /**
     * Get customCode.
     *
     * @return string $customCode
     */
    public function getCustomCode()
    {
        return $this->customCode;
    }

    /**
     * Set customCodeForExport.
     *
     * @param string $customCodeForExport
     *
     * @return $this
     */
    public function setCustomCodeForExport($customCodeForExport)
    {
        $this->customCodeForExport = $customCodeForExport;

        return $this;
    }

    /**
     * Get customCodeForExport.
     *
     * @return string $customCodeForExport
     */
    public function getCustomCodeForExport()
    {
        return $this->customCodeForExport;
    }

    /**
     * Set lastRefresh.
     *
     * @param date $lastRefresh
     *
     * @return $this
     */
    public function setLastRefresh($lastRefresh)
    {
        $this->lastRefresh = $lastRefresh;

        return $this;
    }

    /**
     * Get lastRefresh.
     *
     * @return date $lastRefresh
     */
    public function getLastRefresh()
    {
        return $this->lastRefresh;
    }

    /**
     * Set createdAt.
     *
     * @param date $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set newOntologyToMap.
     *
     * @param bool $newOntologyToMap
     *
     * @return $this
     */
    public function setNewOntologyToMap($newOntologyToMap)
    {
        $this->newOntologyToMap = $newOntologyToMap;

        return $this;
    }

    /**
     * Get newOntologyToMap.
     *
     * @return bool $newOntologyToMap
     */
    public function getNewOntologyToMap()
    {
        return $this->newOntologyToMap;
    }

    /**
     * Set newTaxonomyToMap.
     *
     * @param bool $newTaxonomyToMap
     *
     * @return $this
     */
    public function setNewTaxonomyToMap($newTaxonomyToMap)
    {
        $this->newTaxonomyToMap = $newTaxonomyToMap;

        return $this;
    }

    /**
     * Get newTaxonomyToMap.
     *
     * @return bool $newTaxonomyToMap
     */
    public function getNewTaxonomyToMap()
    {
        return $this->newTaxonomyToMap;
    }

    /**
     * Set preventImportIfNoCategories.
     *
     * @param bool $preventImportIfNoCategories
     *
     * @return $this
     */
    public function setPreventImportIfNoCategories($preventImportIfNoCategories)
    {
        $this->preventImportIfNoCategories = $preventImportIfNoCategories;

        return $this;
    }

    /**
     * Get preventImportIfNoCategories.
     *
     * @return bool $preventImportIfNoCategories
     */
    public function getPreventImportIfNoCategories()
    {
        return $this->preventImportIfNoCategories;
    }

    /**
     * Get the value of mainConfigUpdatedAt
     */ 
    public function getMainConfigUpdatedAt()
    {
        if (is_int($this->mainConfigUpdatedAt)) {
            $date = new \DateTime();
            $date->setTimestamp($this->mainConfigUpdatedAt);
            return $date;
        }
        return $this->mainConfigUpdatedAt;
    }

    /**
     * Set the value of mainConfigUpdatedAt
     *
     * @return  self
     */ 
    public function setMainConfigUpdatedAt($mainConfigUpdatedAt)
    {
        $this->mainConfigUpdatedAt = $mainConfigUpdatedAt;

        return $this;
    }

    /**
     * Get the value of moderateElements
     */ 
    public function getModerateElements()
    {
        return $this->moderateElements;
    }

    /**
     * Set the value of moderateElements
     *
     * @return  self
     */ 
    public function setModerateElements($moderateElements)
    {
        $this->moderateElements = $moderateElements;

        return $this;
    }
}
