<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

abstract class ElementStatus
{
    const Duplicate = -6;
    const ModifiedPendingVersion = -5;
    const Deleted = -4;
    const CollaborativeRefused = -3;
    const AdminRefused = -2;
    const PendingModification = -1;
    const PendingAdd = 0;
    const AdminValidate = 1;
    const CollaborativeValidate = 2;
    const AddedByAdmin = 3;
    const ModifiedByAdmin = 4;
    const ModifiedByOwner = 5;
    const ModifiedFromHash = 6; // in the emails we provide a link to edit the element with a hash validation
    const Imported = 7;
}

abstract class ModerationState
{
    const GeolocError = -2;
    const NoOptionProvided = -1;
    const NotNeeded = 0;
    const ReportsSubmitted = 1;
    const VotesConflicts = 2;
    const PendingForTooLong = 3;
    const PotentialDuplicate = 4;
}

/**
 * @MongoDB\EmbeddedDocument
 * @Vich\Uploadable
 */
class ElementImage extends EmbeddedImage
{
    protected $vichUploadFileKey = 'element_image';
}

/**
 * @MongoDB\EmbeddedDocument
 * @Vich\Uploadable
 */
class ElementFile extends AbstractFile
{
    protected $vichUploadFileKey = 'element_file';
}

/**
 * Element.
 *
 * @MongoDB\Document(repositoryClass="App\Repository\ElementRepository")
 * @Vich\Uploadable
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"geo"="2d"})
 * })
 */
class Element
{
    /**
     * Main properties available on all elements
     */
    const CORE_FIELDS = ['id', 'name', 'categories', 'streetAddress', 'addressLocality', 'postalCode', 'addressCountry', 'latitude', 'longitude', 'images', 'files', 'owner', 'source', 'sourceKey', 'openHours', 'email', 'customFormatedAddress'];

    /**
     * @var int
     *
     * @MongoDB\Id(strategy="ALNUM")
     */
    public $id;

    /**
     * See ElementStatus.
     *
     * @MongoDB\Field(type="int") @MongoDB\Index
     */
    private $status;

    /**
     * If element need moderation we write here the type of modification needed.
     *
     * @MongoDB\Field(type="int") @MongoDB\Index
     */
    private $moderationState = 0;

    /**
     * @var \stdClass
     *
     * Users can report some problem related to the Element (no more existing, wrong informations...)
     *
     * @MongoDB\ReferenceMany(targetDocument="App\Document\UserInteractionReport", cascade={"persist", "remove"})
     */
    private $reports;

    /**
     * @var \stdClass
     *
     * History of users contributions (add, edit, by whom, how many votes etc...)
     *
     * @MongoDB\ReferenceMany(targetDocument="App\Document\UserInteractionContribution", cascade={"persist", "remove"})
     */
    private $contributions;

    /**
     * @var \stdClass
     *
     * When a user propose a modification to an element, the modified element in saved in this attributes,
     * so we keep recording both versions (the old one and the new one) and so we can display the diff
     *
     * @MongoDB\ReferenceOne(targetDocument="App\Document\Element", cascade={"persist", "remove"})
     */
    private $modifiedElement;

    /**
     * Labels/Tags added to an element by specific organisations/people.
     *
     * @MongoDB\ReferenceMany(targetDocument="App\Document\Stamp")
     */
    private $stamps;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    public $name;

    /**
     * @MongoDB\EmbedOne(targetDocument="App\Document\Coordinates")
     */
    public $geo;

    /**
     * @var string
     *
     * Complete address
     *
     * @MongoDB\EmbedOne(targetDocument="App\Document\PostalAddress")
     */
    private $address;

    /**
     * @var \stdClass
     *
     * The options filled by the element, with maaybe some description attached to them
     *
     * @MongoDB\EmbedMany(targetDocument="App\Document\OptionValue")
     */
    private $optionValues;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $optionsString;

    /**
     * @var string
     * @MongoDB\Field(type="string") @MongoDB\Index
     */
    private $email;

    /**
     * @var \stdClass
     *
     * Structured OpenHours
     *
     * @MongoDB\EmbedOne(targetDocument="App\Document\OpenHours")
     */
    private $openHours;

    /**
     * Images, photos, logos, linked to an element.
     *
     * @MongoDB\EmbedMany(targetDocument="App\Document\ElementImage")
     */
    private $images;

    /**
     * Files linked to an element.
     *
     * @MongoDB\EmbedMany(targetDocument="App\Document\ElementFile")
     */
    private $files;

    /**
     * @var string
     *
     * All the custom attributes belonging to the Element
     *
     * @MongoDB\Field(type="hash")
     */
    private $data = [];

    /**
     * @var string
     *
     * A key to clarify the source of the information, i.e. from wich organization/source the
     * element has been imported
     *
     * @MongoDB\Field(type="string") @MongoDB\Index
     */
    public $sourceKey = '';

    /**
     * The source from where the element has been imported or created.
     *
     * @MongoDB\ReferenceOne(targetDocument="App\Document\Import") @MongoDB\Index
     */
    private $source;

    /**
     * @var string
     *
     * If element has been imported, this is the Id of the element in the previous database
     *
     * @MongoDB\Field(type="string") @MongoDB\Index
     */
    private $oldId;

    /**
     * potential duplicates stored by detect duplicate bulk action.
     * @MongoDB\Index
     * @MongoDB\ReferenceMany(targetDocument="App\Document\Element")
     */
    private $potentialDuplicates;

    /**
     * To simlifu duplicates process, we store the element which have been treated in the duplicates detection
     * Because if we check duplicates for element A, and element B and C are detected as potential duplicates, then
     * we do not detect duplicates for B and C.
     * @MongoDB\Index
     * @MongoDB\Field(type="bool", nullable=true)
     */
    private $isDuplicateNode = false;

    /**
     * Mark some element as Non duplicates, so if we run again the duplicate detection they will not be detected.
     * @MongoDB\Index
     * @MongoDB\ReferenceMany(targetDocument="App\Document\Element")
     */
    private $nonDuplicates;


    /** @MongoDB\Field(type="string") @MongoDB\Index */
    private $duplicateOf;
    
    /**
     * @var string
     *
     * The Compact Json representation of the Element. We save it so we don't have to serialize the element
     * each time.
     * The compact json is a small array with the basic informations of the element : id, name, coordinates, optionsValues
     *
     * @MongoDB\Field(type="string")
     */
    private $compactJson;

    /**
     * @var string
     *
     * The complete Json representation of the Element. We save it so we don't have to serialize the element
     * each time
     *
     * @MongoDB\Field(type="string")
     */
    private $baseJson;

    /**
     * @var string
     *
     * Somes special field returned only for admins. this adminJson is concatenated to the baseJson
     *
     * @MongoDB\Field(type="string")
     */
    private $adminJson;

    /**
     * @var date
     *
     * @MongoDB\Field(type="date") @MongoDB\Index
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @var date
     *
     * @MongoDB\Field(type="date") @MongoDB\Index
     */
    private $updatedAt;

    /**
     * @MongoDB\Field(type="string")
     */
    private $randomHash;

    /**
     * @MongoDB\Field(type="string") @MongoDB\Index
     */
    private $userOwnerEmail;

    /**
     * Shorcut to know if this element is managed by a dynamic source.
     *
     * @MongoDB\Field(type="bool") @MongoDB\Index
     */
    private $isExternal;

    /**
     * When actions are made by many person (like moderation, duplicates check...) we lock the elements currently proceed by someone
     * so noone else make action on the same element.
     *
     * @MongoDB\Field(type="int") @MongoDB\Index
     */
    private $lockUntil = 0;

    /** @MongoDB\Field(type="string") @MongoDB\Index */
    private $lockedByUserEmail = '';

    /** @MongoDB\Field(notSaved=true) */
    public $score;

    private $preventJsonUpdate = false;

    private $preventLinksUpdate = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!$this->getRandomHash()) {
            $this->updateRandomHash();
        }
        $this->potentialDuplicates = new \Doctrine\Common\Collections\ArrayCollection();
        $this->nonDuplicates = new \Doctrine\Common\Collections\ArrayCollection();

        if (!$this->isPendingModification()) $this->setModifiedElement(null);
        if (!$this->geo) $this->geo = new Coordinates();
    }

    // automatically resolve moderation error
    public function checkForModerationStillNeeded()
    {
        if (ModerationState::NotNeeded == $this->getModerationState()) {
            return;
        }

        $needed = true;

        switch ($this->getModerationState()) {
            case ModerationState::VotesConflicts:
            case ModerationState::PendingForTooLong:
                if (!$this->isPending()) {
                    $needed = false;
                }
                break;
            case ModerationState::NoOptionProvided:
                if (!$this->isDynamicImported() && $this->countOptionsValues() > 0) {
                    $needed = false;
                }
                break;
            case ModerationState::GeolocError:
                if (0 != $this->getGeo()->getLatitude() && 0 != $this->getGeo()->getLongitude()) {
                    $needed = false;
                }
                break;
        }

        if (!$needed) {
            $this->setModerationState(ModerationState::NotNeeded);
        }
    }

    public function isEditable()
    {
        if (!$this->getSource()) return true;
        return !$this->getIsExternal() || $this->isSynchedWithExternalDatabase();
    }

    public function isSynchedWithExternalDatabase()
    {
        return $this->isFromOsm() && $this->getSource()->getIsSynchronized();
    }

    public function isFromOsm()
    {
        return $this->getIsExternal() && $this->getSource()->getSourceType() == 'osm';
    }

    public function getOsmUrl($config)
    {
        if (!$this->isFromOsm()) return '';
        return $config->getOsm()->getFormattedOsmHost() . $this->getProperty('osm_type') . '/' . $this->getOldId();
    }

    public function getShowUrlFromController($router)
    {
        $url = $router->generate('gogo_directory_showElement', ['id' => $this->getId()]);

        return str_replace('%23', '#', $url);
    }

    public function updateRandomHash()
    {
        $this->setRandomHash(uniqid());
    }

    public function updateTimestamp()
    {
        $this->setUpdatedAt(time());
    }

    public function resetOptionsValues()
    {
        $this->optionValues = [];
    }

    public function resetImages()
    {
        $this->images = [];
    }

    public function resetFiles()
    {
        $this->files = [];
    }

    public function resetContributions()
    {
        $this->contributions = [];
    }

    public function resetReports()
    {
        $this->reports = [];
    }

    public function getUnresolvedReports()
    {
        if (null == $this->getReports()) {
            return;
        }
        $reports = $this->getArrayFromCollection($this->getReports());
        $result = array_filter($reports, function ($e) { return !$e->getIsResolved(); });

        return $result;
    }

    public function getContributionsAndResolvedReports()
    {
        if (null == $this->getReports() || null == $this->getContributions()) {
            return;
        }
        $reports = $this->getArrayFromCollection($this->getReports());
        $contributions = $this->getArrayFromCollection($this->getContributions());
        $resolvedReports = array_filter($reports, function ($e) { return $e->getIsResolved(); });
        $contributions = array_filter($contributions, function ($e) { return $e->getStatus() != ElementStatus::ModifiedPendingVersion; });
        $result = array_merge($resolvedReports, $contributions);
        usort($result, function ($a, $b) { return $b->getTimestamp() - $a->getTimestamp(); });

        return $result;
    }

    public function hasValidContributionMadeBy($userEmail)
    {
        $contribs = $this->getArrayFromCollection($this->getContributions());
        $userValidContributionsOnElement = array_filter($contribs, function ($contribution) use ($userEmail) {
            return $contribution->countAsValidContributionFrom($userEmail);
        });

        return count($userValidContributionsOnElement) > 0;
    }

    public function countOptionsValues()
    {
        if (!$this->getOptionValues()) {
            return 0;
        }
        if (is_array($this->getOptionValues())) {
            return count($this->getOptionValues());
        }

        return $this->getOptionValues()->count();
    }

    public function getSortedOptionsValues()
    {
        $sortedOptionsValues = [];
        if ($this->optionValues) {
            $sortedOptionsValues = is_array($this->optionValues) ? $this->optionValues : $this->optionValues->toArray();
            usort($sortedOptionsValues, function ($a, $b) { return $a->getIndex() - $b->getIndex(); });
        }

        return $sortedOptionsValues;
    }

    public function getNonDuplicatesIds()
    {
        $result = [];
        if ($this->nonDuplicates) {
            foreach($this->nonDuplicates as $nonDuplicate)
                $result[] = $nonDuplicate->getId();
        }
        if ($this->getId()) {
            $result[] = $this->getId();
        }

        return $result;
    }

    public function isPotentialDuplicate()
    {
        return ModerationState::PotentialDuplicate == $this->moderationState;
    }

    public function isDynamicImported()
    {
        return $this->isExternal;
    }

    public function getJson($includeAdminJson = false)
    {
        $result = '{' . $this->baseJson;
        if ($includeAdminJson && $this->adminJson && '' != $this->adminJson) {
            $result .= ','. $this->adminJson;
        }
        return $result . '}';
    }

    public function isPending()
    {
        return $this->isPendingAdd() || $this->isPendingModification();
    }

    public function isPendingAdd()
    {
        return ElementStatus::PendingAdd == $this->status;
    }

    public function isPendingModification()
    {
        return ElementStatus::PendingModification == $this->status;
    }

    public function isVisible()
    {
        return $this->status >= ElementStatus::PendingModification;
    }

    public function isDeleted()
    {
        return $this->status <= ElementStatus::AdminRefused;
    }

    public function isValid()
    {
        return $this->status >= ElementStatus::AdminValidate;
    }

    public function havePendingReports()
    {
        return ModerationState::ReportsSubmitted == $this->moderationState;
    }

    public function getCurrContribution()
    {
        $contributions = $this->getContributions();
        if (is_array($contributions)) {
            if (count($contributions) > 0) {
                $currContrib = array_slice($contributions, -1);

                return array_pop($currContrib);
            }

            return null;
        } else {
            return $contributions ? $contributions->last() : null;
        }
    }

    public function getVotes()
    {
        return $this->getCurrContribution() ? $this->getCurrContribution()->getVotes() : [];
    }

    public function getVotesArray()
    {
        if (!$this->getCurrContribution() || is_array($this->getCurrContribution()->getVotes())) {
            return [];
        }

        return $this->getCurrContribution()->getVotes()->toArray();
    }

    public function isLastContributorEqualsTo($user, $userEmail)
    {
        return $this->getCurrContribution() ? $this->getCurrContribution()->isMadeBy($user, $userEmail) : false;
    }

    public function getFormatedAddress()
    {
        return $this->address ? $this->address->getFormatedAddress() : '';
    }

    public function getOptionIds()
    {
        $result = [];
        if ($this->getOptionValues()) {
            foreach ($this->getOptionValues() as $optionsValue) {
                $result[] = (string) $optionsValue->getOptionId();
            }
        }
        return $result;
    }

    // Manage easily options, with taking care of index and description
    public function setOptionIds($optionsIds)
    {
        foreach ($this->getOptionValues() as $optionValue) {
            if (!in_array($optionValue->getOptionId(), $optionsIds))
                $this->removeOptionValue($optionValue);
        }
        $optionIdsToAdd = array_diff($optionsIds, $this->getOptionIds());
        foreach($optionIdsToAdd as $optionId) {
            $newOptionValue = new OptionValue($optionId);
            $this->addOptionValue($newOptionValue);
        }
        return $this;
    }

    public function reset()
    {
        $this->name = null;
        $this->address = null;
        $this->resetOptionsValues();
        $this->openHours = null;
        $this->data = null;
    }

    public function setCustomData($data)
    {
        if (null != $data) {
            if (array_key_exists('email', $data)) {
                $this->setEmail($data['email']);
            }
        }
        if ($this->getData() && $data) {
            // keeping also old data
            $data = array_merge($this->getData(), $data);
        }
        $this->setData($data);
    }

    public function setCustomProperty($key, $value) {
        $this->data[$key] = $value;
    }

    public function getProperty($key)
    {
        if (property_exists($this, $key)) {
            $method = 'get'.ucfirst($key);
            if ('images' == $key) {
                $method = 'getImagesUrls';
            }
            if ('files' == $key) {
                $method = 'getFilesUrls';
            }
            if ('address' == $key) {
                return $this->getAddress()->toJson();
            }
            return $this->$method();
        } else {
            return $this->getCustomProperty($key);
        }
    }

    public function getCustomProperty($key)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }

    public function deleteCustomProperty($key)
    {
        if (isset($this->data[$key])) unset($this->data[$key]);
    }
    /**
     * Set status.
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus($newStatus)
    {
        $this->status = $newStatus;

        return $this;
    }

    public function __toString()
    {
        return $this->getName() ? $this->getName() : '';
    }

    /**
     * Get id.
     *
     * @return custom_id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get id.
     *
     * @return custom_id $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set openHours.
     *
     * @param object_id $openHours
     *
     * @return $this
     */
    public function setOpenHours($openHours)
    {
        $this->openHours = $openHours;

        return $this;
    }

    /**
     * Get openHours.
     *
     * @return object_id $openHours
     */
    public function getOpenHours()
    {
        return $this->openHours;
    }

    /**
     * Add optionValue.
     *
     * @param App\Document\OptionValue $optionValue
     */
    public function addOptionValue(\App\Document\OptionValue $optionValue)
    {
        $this->optionValues[] = $optionValue;
    }

    /**
     * Remove optionValue.
     *
     * @param App\Document\OptionValue $optionValue
     */
    public function removeOptionValue(\App\Document\OptionValue $optionValue)
    {
        $this->optionValues->removeElement($optionValue);
    }

    /**
     * Get optionValues.
     *
     * @return \Doctrine\Common\Collections\Collection $optionValues
     */
    public function getOptionValues()
    {
        return $this->optionValues ?? [];
    }

    public function setOptionValues($optionValues)
    {
        $this->optionValues = $optionValues;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set compactJson.
     *
     * @param string $compactJson
     *
     * @return $this
     */
    public function setCompactJson($compactJson)
    {
        $this->compactJson = $compactJson;

        return $this;
    }

    /**
     * Get compactJson.
     *
     * @return string $compactJson
     */
    public function getCompactJson()
    {
        return $this->compactJson;
    }

    /**
     * Set baseJson.
     *
     * @param string $baseJson
     *
     * @return $this
     */
    public function setBaseJson($baseJson)
    {
        $this->baseJson = $baseJson;

        return $this;
    }

    /**
     * Get baseJson.
     *
     * @return string $baseJson
     */
    public function getBaseJson()
    {
        return $this->baseJson;
    }

    /**
     * Add report.
     *
     * @param App\Document\Report $report
     */
    public function addReport(\App\Document\UserInteractionReport $report)
    {
        $report->setElement($this);
        $this->reports[] = $report;
        $this->setModerationState(ModerationState::ReportsSubmitted);
    }

    /**
     * Remove report.
     *
     * @param App\Document\Report $report
     */
    public function removeReport(\App\Document\UserInteractionReport $report)
    {
        $this->reports->removeElement($report);
    }

    /**
     * Get reports.
     *
     * @return \Doctrine\Common\Collections\Collection $reports
     */
    public function getReports()
    {
        return $this->reports;
    }

    private function getArrayFromCollection($collection)
    {
        if (null == $collection) {
            return [];
        } elseif (is_array($collection)) {
            return $collection;
        } else {
            return $collection->toArray();
        }
    }

    /**
     * Set created.
     *
     * @param date $created
     *
     * @return $this
     */
    public function setCreatedAt($created)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get created.
     *
     * @return date $created
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updated.
     *
     * @param date $updated
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return date $updated
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set statusMessage.
     *
     * @param string $statusMessage
     *
     * @return $this
     */
    public function setModerationState($moderationState)
    {
        // Do not overide a potential duplicate state with other moderation types
        if ($this->moderationState == ModerationState::PotentialDuplicate 
        && !$moderationState == ModerationState::NotNeeded)
            return;
        $this->moderationState = $moderationState;

        return $this;
    }

    /**
     * Get statusMessage.
     *
     * @return string $statusMessage
     */
    public function getModerationState()
    {
        return $this->moderationState;
    }

    /**
     * Set modifiedElement.
     *
     * @param App\Document\Element $modifiedElement
     *
     * @return $this
     */
    public function setModifiedElement($modifiedElement)
    {
        $this->modifiedElement = $modifiedElement;

        return $this;
    }

    /**
     * Get modifiedElement.
     *
     * @return App\Document\Element $modifiedElement
     */
    public function getModifiedElement()
    {
        return $this->modifiedElement;
    }

    /**
     * Set sourceKey.
     *
     * @param string $sourceKey
     *
     * @return $this
     */
    public function setSourceKey($sourceKey)
    {
        $this->sourceKey = $sourceKey;

        return $this;
    }

    /**
     * Get sourceKey.
     *
     * @return string $sourceKey
     */
    public function getSourceKey()
    {
        return $this->sourceKey;
    }

    /**
     * Add contribution.
     *
     * @param App\Document\UserInteractionContribution $contribution
     */
    public function addContribution(\App\Document\UserInteractionContribution $contribution)
    {
        $contribution->setElement($this);
        $this->contributions[] = $contribution;
    }

    /**
     * Remove contribution.
     *
     * @param App\Document\UserInteractionContribution $contribution
     */
    public function removeContribution(\App\Document\UserInteractionContribution $contribution)
    {
        $this->contributions->removeElement($contribution);
    }

    /**
     * Get contributions.
     *
     * @return \Doctrine\Common\Collections\Collection $contributions
     */
    public function getContributions()
    {
        // Sometime the association between Element and Contribution is broken, and so
        // need to sensure the contribution exists
        // #UglyFix
        if (!$this->contributions) {
            return [];
        }
        $contribs = [];
        foreach ($this->contributions as $contrib) {
            try {
                if (null != $contrib->getCreatedAt()) {
                    array_push($contribs, $contrib);
                }
            } catch (\Exception $e) {
                $this->removeContribution($contrib);
            }
        }

        return $contribs;
    }

    /**
     * Set oldId.
     *
     * @param string $oldId
     *
     * @return $this
     */
    public function setOldId($oldId)
    {
        $this->oldId = $oldId;

        return $this;
    }

    /**
     * Get oldId.
     *
     * @return string $oldId
     */
    public function getOldId()
    {
        return $this->oldId;
    }

    /**
     * Set geo.
     *
     * @param App\Document\Coordinates $geo
     *
     * @return $this
     */
    public function setGeo(\App\Document\Coordinates $geo)
    {
        $this->geo = $geo;
        return $this;
    }

    /**
     * Get geo.
     *
     * @return App\Document\Coordinates $geo
     */
    public function getGeo()
    {
        return $this->geo;
    }

    /**
     * Set address.
     *
     * @param App\Document\PostalAddress $address
     *
     * @return $this
     */
    public function setAddress(\App\Document\PostalAddress $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return App\Document\PostalAddress $address
     */
    public function getAddress()
    {
        if (!$this->address) return new PostalAddress();
        return $this->address;
    }

    /**
     * Set adminJson.
     *
     * @param string $adminJson
     *
     * @return $this
     */
    public function setAdminJson($adminJson)
    {
        $this->adminJson = $adminJson;

        return $this;
    }

    /**
     * Get adminJson.
     *
     * @return string $adminJson
     */
    public function getAdminJson()
    {
        return $this->adminJson;
    }

    /**
     * Set optionsString.
     *
     * @param string $optionsString
     *
     * @return $this
     */
    public function setOptionsString($optionsString)
    {
        $this->optionsString = $optionsString;

        return $this;
    }

    /**
     * Get optionsString.
     *
     * @return string $optionsString
     */
    public function getOptionsString()
    {
        return $this->optionsString;
    }

    // in the UI, a Category correspond to an Option in the code...
    public function getCategoriesIds()
    {
        return $this->getOptionIds();
    }

    public function getCategoriesNames()
    {
        return explode(',', str_replace(' ', '', $this->optionsString));
    }

    /**
     * Set randomHash.
     *
     * @param string $randomHash
     *
     * @return $this
     */
    public function setRandomHash($randomHash)
    {
        $this->randomHash = $randomHash;

        return $this;
    }

    /**
     * Get randomHash.
     *
     * @return string $randomHash
     */
    public function getRandomHash()
    {
        return $this->randomHash;
    }

    /**
     * Set userOwnerEmail.
     *
     * @param string $userOwnerEmail
     *
     * @return $this
     */
    public function setUserOwnerEmail($userOwnerEmail)
    {
        $this->userOwnerEmail = $userOwnerEmail;

        return $this;
    }

    /**
     * Get userOwnerEmail.
     *
     * @return string $userOwnerEmail
     */
    public function getUserOwnerEmail()
    {
        return $this->userOwnerEmail;
    }

    /**
     * Add stamp.
     *
     * @param App\Document\Stamp $stamp
     */
    public function addStamp(\App\Document\Stamp $stamp)
    {
        $this->stamps[] = $stamp;
    }

    /**
     * Remove stamp.
     *
     * @param App\Document\Stamp $stamp
     */
    public function removeStamp(\App\Document\Stamp $stamp)
    {
        $this->stamps->removeElement($stamp);
    }

    public function getStampIds()
    {
        return array_map(function ($el) { return $el->getId(); }, $this->getStamps()->toArray());
    }

    /**
     * Get stamps.
     *
     * @return \Doctrine\Common\Collections\Collection $stamps
     */
    public function getStamps()
    {
        return $this->stamps;
    }

    /**
     * Add image.
     *
     * @param App\Document\Image $image
     */
    public function addImage($image)
    {
        $this->images[] = $image;
    }

    public function setImages($images)
    {
        if (!is_array($images)) {
            $images = $images->toArray();
        }
        $this->images = array_filter($images, function ($el) {
            return '' != $el->getExternalImageUrl() || '' != $el->getFileUrl();
        });
    }

    /**
     * Remove image.
     *
     * @param App\Document\Image $image
     */
    public function removeImage($image)
    {
        $this->images->removeElement($image);
    }

    /**
     * Get images.
     *
     * @return \Doctrine\Common\Collections\Collection $images
     */
    public function getImages()
    {
        return $this->images;
    }

    public function getImagesArray()
    {
        if (!$this->images) {
            return [];
        }

        return is_array($this->images) ? $this->images : $this->images->toArray();
    }

    public function getImagesUrls()
    {
        $result = [];
        if($this->images != null) {
            foreach ($this->images as $image) {
                $result[] = $image->getImageUrl();
            }
        }

        return $result;
    }

    /**
     * Add potentialDuplicate.
     *
     * @param App\Document\Element $potentialDuplicate
     */
    public function addPotentialDuplicate(\App\Document\Element $potentialDuplicate)
    {
        if (!in_array($potentialDuplicate, $this->potentialDuplicates->toArray()))
            $this->potentialDuplicates[] = $potentialDuplicate;
    }

    /**
     * Remove potentialDuplicate.
     *
     * @param App\Document\Element $potentialDuplicate
     */
    public function removePotentialDuplicate(\App\Document\Element $potentialDuplicate)
    {
        if (is_array($this->potentialDuplicates)) {
            $key = array_search($potentialDuplicate, $this->$this->potentialDuplicates);
            unset($this->potentialDuplicates[$key]);
            $potDupsCount = count($this->potentialDuplicates);
        } else {
            $this->potentialDuplicates->removeElement($potentialDuplicate);
            $potDupsCount = $this->potentialDuplicates->count();
        }
        if ($potDupsCount == 0) {
            $this->setIsDuplicateNode(false);
            $this->setModerationState(ModerationState::NotNeeded);
        }
        return $this;
    }

    /**
     * Get potentialDuplicates.
     *
     * @return \Doctrine\Common\Collections\Collection $potentialDuplicates
     */
    public function getPotentialDuplicates()
    {
        return $this->potentialDuplicates;
    }

    public function clearPotentialDuplicates()
    {
        $this->potentialDuplicates = [];

        return $this;
    }

    /**
     * Add nonDuplicate.
     *
     * @param App\Document\Element $nonDuplicate
     */
    public function addNonDuplicate(\App\Document\Element $nonDuplicate)
    {
        $this->nonDuplicates[] = $nonDuplicate;
    }

    /**
     * Remove nonDuplicate.
     *
     * @param App\Document\Element $nonDuplicate
     */
    public function removeNonDuplicate(\App\Document\Element $nonDuplicate)
    {
        $this->nonDuplicates->removeElement($nonDuplicate);
    }

    /**
     * Get nonDuplicates.
     *
     * @return \Doctrine\Common\Collections\Collection $nonDuplicates
     */
    public function getNonDuplicates()
    {
        return $this->nonDuplicates;
    }

    /**
     * Set isDuplicateNode.
     *
     * @param bool $isDuplicateNode
     *
     * @return $this
     */
    public function setIsDuplicateNode($isDuplicateNode)
    {
        $this->isDuplicateNode = $isDuplicateNode;

        return $this;
    }

    /**
     * Get isDuplicateNode.
     *
     * @return bool $isDuplicateNode
     */
    public function getIsDuplicateNode()
    {
        return $this->isDuplicateNode;
    }

    /**
     * Set lockUntil.
     *
     * @param int $lockUntil
     *
     * @return $this
     */
    public function setLockUntil($lockUntil)
    {
        $this->lockUntil = $lockUntil;

        return $this;
    }

    /**
     * Get lockUntil.
     *
     * @return int $lockUntil
     */
    public function getLockUntil()
    {
        return $this->lockUntil;
    }

    /**
     * Set source.
     *
     * @param App\Document\Import $source
     *
     * @return $this
     */
    public function setSource(\App\Document\Import $source)
    {
        $this->source = $source;
        if ($source->isDynamicImport()) {
            $this->setIsExternal(true); // shortcut for easy querying
        }
        $this->setSourceKey($source->getSourceName());

        return $this;
    }

    /**
     * Get source.
     *
     * @return App\Document\Import $source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set data.
     *
     * @param hash $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data.
     *
     * @return hash $data
     */
    public function getData()
    {
        return $this->data;
    }

    public function getSortedData()
    {
        ksort($this->data);
        return $this->data;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string $email
     */
    public function getEmail()
    {
        if ($this->email) {
            return $this->email;
        }
        if ($this->data && array_key_exists('email', $this->data)) {
            return $this->data['email'];
        }
        return '';
    }

    public function setPreventJsonUpdate($preventJsonUpdate)
    {
        $this->preventJsonUpdate = $preventJsonUpdate;

        return $this;
    }

    public function getPreventJsonUpdate()
    {
        return $this->preventJsonUpdate || false;
    }

    public function setPreventLinksUpdate($preventLinksUpdate)
    {
        $this->preventLinksUpdate = $preventLinksUpdate;

        return $this;
    }

    public function getPreventLinksUpdate()
    {
        return $this->preventLinksUpdate || false;
    }

    /**
     * Set isExternal.
     *
     * @param bool $isExternal
     *
     * @return $this
     */
    public function setIsExternal($isExternal)
    {
        $this->isExternal = $isExternal;

        return $this;
    }

    /**
     * Get isExternal.
     *
     * @return bool $isExternal
     */
    public function getIsExternal()
    {
        return $this->isExternal;
    }

    /**
     * Add file.
     *
     * @param App\Document\ElementFile $file
     */
    public function addFile($file)
    {
        $this->files[] = $file;
    }

    public function setFiles($files)
    {
        if (!is_array($files)) {
            $files = $files->toArray();
        }
        $this->files = array_filter($files, function ($el) {
            return '' != $el->getFileUrl();
        });
    }

    /**
     * Remove file.
     *
     * @param App\Document\ElementFile $file
     */
    public function removeFile($file)
    {
        $this->files->removeElement($file);
    }

    /**
     * Get files.
     *
     * @return \Doctrine\Common\Collections\Collection $files
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function getFilesArray()
    {
        if (!$this->files) {
            return [];
        }

        return is_array($this->files) ? $this->files : $this->files->toArray();
    }

    public function getFilesUrls()
    {
        $result = [];
        if($this->files != null) {
            foreach ($this->files as $file) {
                $result[] = $file->getFileUrl();
            }
        }

        return $result;
    }

    /**
     * Get the value of score
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set the value of score
     *
     * @return  self
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get the value of lockedByUserEmail
     */ 
    public function getLockedByUserEmail()
    {
        return $this->lockedByUserEmail;
    }

    /**
     * Set the value of lockedByUserEmail
     *
     * @return  self
     */ 
    public function setLockedByUserEmail($lockedByUserEmail)
    {
        $this->lockedByUserEmail = $lockedByUserEmail;

        return $this;
    }

    /**
     * Get the value of duplicateOf
     */ 
    public function getDuplicateOf()
    {
        return $this->duplicateOf;
    }

    /**
     * Set the value of duplicateOf
     *
     * @return  self
     */ 
    public function setDuplicateOf($duplicateOf)
    {
        $this->duplicateOf = $duplicateOf;

        return $this;
    }
}
