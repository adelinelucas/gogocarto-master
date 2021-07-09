<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;

/**
 * Category.
 *
 * @MongoDB\HasLifecycleCallbacks
 * @MongoDB\Document(repositoryClass="App\Repository\CategoryRepository")
 */
class Category
{
    /**
     * @var int
     * @MongoDB\Id(strategy="INCREMENT")
     */
    private $id;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $customId;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $name = '';

    /**
     * @var string
     * @Exclude(if="object.getNameShort() == object.getName()")
     * @MongoDB\Field(type="string")
     */
    private $nameShort;

    /**
     * @MongoDB\ReferenceOne(targetDocument="App\Document\Option", inversedBy="subcategories")
     */
    public $parent;

    /**
     * @var int
     * @Exclude
     * @MongoDB\Field(type="int")
     */
    private $index = 0;

    /**
     * @var bool
     * @Exclude
     * @MongoDB\Field(type="boolean")
     */
    private $singleOption = false;

    /**
     * @var bool
     * @Exclude(if="object.getIsMandatory() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $isMandatory = false;

    /**
     * @var bool
     * @Exclude
     * @MongoDB\Field(type="boolean")
     */
    private $useFreeTags = false;

    /**
     * @var bool
     * @Exclude(if="object.getEnableDescription() == false")
     * @MongoDB\Field(type="boolean")
     */
    private $enableDescription = false;

    /**
     * @var string
     * @Exclude(if="object.getEnableDescription() == false")
     * @MongoDB\Field(type="string")
     */
    private $descriptionLabel = '';

    /**
     * @var bool
     * @Exclude(if="object.getDisplayInMenu() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $displayInMenu = true;

    /**
     * @var bool
     * @Exclude(if="object.getDisplayInInfoBar() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $displayInInfoBar = true;

    /**
     * @var bool
     * @Exclude(if="object.getDisplayInForm() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $displayInForm = true;

    /**
     * @var bool
     * @Exclude(if="object.getUseForFiltering() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $useForFiltering = true;

    /**
     * @var bool
     * @Exclude(if="object.getDisplaySuboptionsInline() == false")
     * @MongoDB\Field(type="boolean")
     */
    private $displaySuboptionsInline = false;

    /**
     * @var string
     * @Exclude
     * @MongoDB\Field(type="string")
     */
    private $pickingOptionText;

    /**
     * @var string
     * @Exclude(if="object.getShowExpanded() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $showExpanded = true;

    /**
     * @var string
     * @Exclude(if="object.getUnexpandable() == false")
     * @MongoDB\Field(type="boolean")
     */
    private $unexpandable = false;

    /**
     * @var bool
     * @Exclude
     * If Category is loaded by a fixture
     */
    private $isFixture = false;

    /**
     * @var string
     * @Exclude
     * @MongoDB\Field(type="boolean") @MongoDB\Index
     */
    private $isRootCategory = false;

    /**
     * @Accessor(getter="getOrderedOptions")
     * Force ordering cause the RefeenceMany sort property does not work when the options order have been modified after been loaded in the original order
     * @Exclude(if="object.getOptionsCount() == 0")
     * @MongoDB\ReferenceMany(targetDocument="App\Document\Option", mappedBy="parent", cascade={"persist", "remove"}, sort={"index"="ASC"})
     */
    private $options;

    public function __construct()
    {
        $this->options = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        $parentName = $this->getParent() ? $this->getParent()->getName().'/' : '';

        return '(Groupe) '.$parentName.$this->getName();
    }

    /** @MongoDB\PreFlush */
    public function onPreFlush()
    {
        $haveNoParent = null === $this->getParent();
        $this->setIsRootCategory($haveNoParent);
    }

    public function getOptionsCount()
    {
        if ($this->options) {
            return $this->options->count();
        }

        return 0;
    }

    public function getOrderedOptions()
    {
        $sortedOptions = is_array($this->options) ? $this->options : $this->options->toArray();
        usort($sortedOptions, function ($a, $b) { return $a->getIndex() - $b->getIndex(); });

        return $sortedOptions;
    }

    public function getAllOptionsIds()
    {
        return $this->recursivelyGetOptionsIds($this);
    }

    private function recursivelyGetOptionsIds($category)
    {
        $result = [];
        foreach ($category->getOptions() as $option) {
            $result[] = $option->getId();
            foreach ($option->getSubcategories() as $childCategory) {
                $result = array_merge($result, $this->recursivelyGetOptionsIds($childCategory));
            }
        }

        return $result;
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

    public function setId($value = null)
    {
        return $this;
    }

    public function getCustomStringId()
    {
        return $this->customId ?: strval($this->id);
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
     * Set nameShort.
     *
     * @param string $nameShort
     *
     * @return $this
     */
    public function setNameShort($nameShort)
    {
        $this->nameShort = $nameShort;

        return $this;
    }

    /**
     * Get nameShort.
     *
     * @return string $nameShort
     */
    public function getNameShort()
    {
        return $this->nameShort;
    }

    /**
     * Add option.
     *
     * @param App\Document\Option $option
     */
    public function addOption(\App\Document\Option $option)
    {
        $option->setParent($this, false);
        $this->options[] = $option;
    }

    /**
     * Remove option.
     *
     * @param App\Document\Option $option
     */
    public function removeOption(\App\Document\Option $option)
    {
        $this->options->removeElement($option);
    }

    /**
     * Get options.
     *
     * @return \Doctrine\Common\Collections\Collection $options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set index.
     *
     * @param int $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Get index.
     *
     * @return int $index
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Set singleOption.
     *
     * @param bool $singleOption
     *
     * @return $this
     */
    public function setSingleOption($singleOption)
    {
        $this->singleOption = $singleOption;

        return $this;
    }

    /**
     * Get singleOption.
     *
     * @return bool $singleOption
     */
    public function getSingleOption()
    {
        return $this->singleOption;
    }

    /**
     * Set enableDescription.
     *
     * @param bool $enableDescription
     *
     * @return $this
     */
    public function setEnableDescription($enableDescription)
    {
        $this->enableDescription = $enableDescription;

        return $this;
    }

    /**
     * Get enableDescription.
     *
     * @return bool $enableDescription
     */
    public function getEnableDescription()
    {
        return $this->enableDescription;
    }

    /**
     * Set pickingOptionText.
     *
     * @param string $pickingOptionText
     *
     * @return $this
     */
    public function setPickingOptionText($pickingOptionText)
    {
        $this->pickingOptionText = $pickingOptionText;

        return $this;
    }

    /**
     * Get pickingOptionText.
     *
     * @return string $pickingOptionText
     */
    public function getPickingOptionText()
    {
        return $this->pickingOptionText;
    }

    /**
     * Set showExpanded.
     *
     * @param bool $showExpanded
     *
     * @return $this
     */
    public function setShowExpanded($showExpanded)
    {
        $this->showExpanded = $showExpanded;

        return $this;
    }

    /**
     * Get showExpanded.
     *
     * @return bool $showExpanded
     */
    public function getShowExpanded()
    {
        return $this->showExpanded;
    }

    /**
     * Set unexpandable.
     *
     * @param bool $unexpandable
     *
     * @return $this
     */
    public function setUnexpandable($unexpandable)
    {
        $this->unexpandable = $unexpandable;

        return $this;
    }

    /**
     * Get unexpandable.
     *
     * @return bool $unexpandable
     */
    public function getUnexpandable()
    {
        return $this->unexpandable;
    }

    /**
     * Set parent.
     *
     * @param App\Document\Option $parent
     *
     * @return $this
     */
    public function setParent($parent, $updateParent = true)
    {
        if ($parent && in_array($parent->getId(), $this->getAllOptionsIds())) {
            // Circular reference
        } else {
            // clearing old parent
            if ($updateParent && $this->parent) {
                $this->parent->removeSubcategory($this, false);
            }

            $this->parent = $parent;
            if ($updateParent && $parent) {
                $parent->addSubcategory($this, false);
            }
        }

        return $this;
    }

    /**
     * Get parent.
     *
     * @return App\Document\Option $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set isFixture.
     *
     * @param bool $isFixture
     *
     * @return $this
     */
    public function setIsFixture($isFixture)
    {
        $this->isFixture = $isFixture;

        return $this;
    }

    /**
     * Get isFixture.
     *
     * @return bool $isFixture
     */
    public function getIsFixture()
    {
        return $this->isFixture;
    }

    /**
     * Set isMandatory.
     *
     * @param bool $isMandatory
     *
     * @return $this
     */
    public function setIsMandatory($isMandatory)
    {
        $this->isMandatory = $isMandatory;

        return $this;
    }

    /**
     * Get isMandatory.
     *
     * @return bool $isMandatory
     */
    public function getIsMandatory()
    {
        return $this->isMandatory;
    }

    /**
     * Set useFreeTags.
     *
     * @param bool $useFreeTags
     *
     * @return $this
     */
    public function setUseFreeTags($useFreeTags)
    {
        $this->useFreeTags = $useFreeTags;

        return $this;
    }

    /**
     * Get useFreeTags.
     *
     * @return bool $useFreeTags
     */
    public function getUseFreeTags()
    {
        return $this->useFreeTags;
    }

    /**
     * Set isRootCategory.
     *
     * @param bool $isRootCategory
     *
     * @return $this
     */
    public function setIsRootCategory($isRootCategory)
    {
        $this->isRootCategory = $isRootCategory;

        return $this;
    }

    /**
     * Get isRootCategory.
     *
     * @return bool $isRootCategory
     */
    public function getIsRootCategory()
    {
        return $this->isRootCategory;
    }

    /**
     * Set displayInMenu.
     *
     * @param bool $displayInMenu
     *
     * @return $this
     */
    public function setDisplayInMenu($displayInMenu)
    {
        $this->displayInMenu = $displayInMenu;

        return $this;
    }

    /**
     * Get displayInMenu.
     *
     * @return bool $displayInMenu
     */
    public function getDisplayInMenu()
    {
        return $this->displayInMenu;
    }

    /**
     * Set displayInInfoBar.
     *
     * @param bool $displayInInfoBar
     *
     * @return $this
     */
    public function setDisplayInInfoBar($displayInInfoBar)
    {
        $this->displayInInfoBar = $displayInInfoBar;

        return $this;
    }

    /**
     * Get displayInInfoBar.
     *
     * @return bool $displayInInfoBar
     */
    public function getDisplayInInfoBar()
    {
        return $this->displayInInfoBar;
    }

    /**
     * Set displayInForm.
     *
     * @param bool $displayInForm
     *
     * @return $this
     */
    public function setDisplayInForm($displayInForm)
    {
        $this->displayInForm = $displayInForm;

        return $this;
    }

    /**
     * Get displayInForm.
     *
     * @return bool $displayInForm
     */
    public function getDisplayInForm()
    {
        return $this->displayInForm;
    }

    /**
     * Set useForFiltering.
     *
     * @param bool $useForFiltering
     *
     * @return $this
     */
    public function setUseForFiltering($useForFiltering)
    {
        $this->useForFiltering = $useForFiltering;

        return $this;
    }

    /**
     * Get useForFiltering.
     *
     * @return bool $useForFiltering
     */
    public function getUseForFiltering()
    {
        return $this->useForFiltering;
    }

    /**
     * Set displaySuboptionsInline.
     *
     * @param bool $displaySuboptionsInline
     *
     * @return $this
     */
    public function setDisplaySuboptionsInline($displaySuboptionsInline)
    {
        $this->displaySuboptionsInline = $displaySuboptionsInline;

        return $this;
    }

    /**
     * Get displaySuboptionsInline.
     *
     * @return bool $displaySuboptionsInline
     */
    public function getDisplaySuboptionsInline()
    {
        return $this->displaySuboptionsInline;
    }

        /**
     * Set customId.
     *
     * @param string $customId
     *
     * @return $this
     */
    public function setCustomId($customId)
    {
        $this->customId = $customId;

        return $this;
    }

    /**
     * Get customId.
     *
     * @return string $customId
     */
    public function getCustomId()
    {
        return $this->customId;
    }

    /**
     * Get the value of descriptionLabel
     *
     * @return  string
     */ 
    public function getDescriptionLabel()
    {
        return $this->descriptionLabel;
    }

    /**
     * Set the value of descriptionLabel
     *
     * @param  string  $descriptionLabel
     *
     * @return  self
     */ 
    public function setDescriptionLabel($descriptionLabel)
    {
        $this->descriptionLabel = $descriptionLabel;

        return $this;
    }
}
