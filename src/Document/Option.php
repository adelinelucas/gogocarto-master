<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;

/**
 * Option.
 *
 * @MongoDB\Document(repositoryClass="App\Repository\OptionRepository")
 * @MongoDB\Index(keys={"name"="text"})
 */
class Option
{
    /**
     * @var int
     * @Accessor(getter="getStringId",setter="setId")
     * @Groups({"semantic"})
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
     * @Groups({"semantic"})
     * @MongoDB\Field(type="string")
     */
    private $name = '';

    /**
     * @var string
     * @Groups({"semantic"})
     * @Exclude(if="object.getNameShort() == object.getName()")
     * @MongoDB\Field(type="string")
     */
    private $nameShort;

    /**
     * @Accessor(getter="getParentOptionId")
     * @Groups({"semantic"})
     * @Exclude(if="object.getParentOptionId() == null")
     * @MongoDB\ReferenceOne(targetDocument="App\Document\Category", inversedBy="options", cascade={"persist"})
     */
    public $parent;

    /**
     * @var int
     * @Exclude
     * @MongoDB\Field(type="int")
     */
    private $index;

    /**
     * @var string
     * @Groups({"semantic"})
     * @MongoDB\Field(type="string")
     */
    private $color;

    /**
     * @var string
     * @Exclude(if="object.getSoftColor() == object.getColor()")
     * @MongoDB\Field(type="string")
     */
    private $softColor;

    /**
     * @var string
     * @Groups({"semantic"})
     * @MongoDB\Field(type="string")
     */
    private $icon;

    /**
     * @var string
     * @Groups({"semantic"})
     * @Exclude(if="object.getTextHelper() == ''")
     * @MongoDB\Field(type="string")
     */
    private $textHelper;

    /**
     * @var string
     * @Groups({"semantic"})
     * @Exclude(if="object.getUrl() == ''")
     * @MongoDB\Field(type="string")
     */
    private $url;

    /**
     * @var bool
     * @Exclude(if="object.getUseIconForMarker() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $useIconForMarker = true;

    /**
     * @var bool
     * @Exclude(if="object.getUseColorForMarker() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $useColorForMarker = true;

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
     * @Exclude(if="object.getDisplayChildrenInMenu() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $displayChildrenInMenu = true;

    /**
     * @var bool
     * @Exclude(if="object.getDisplayChildrenInInfoBar() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $displayChildrenInInfoBar = true;

    /**
     * @var bool
     * @Exclude(if="object.getDisplayChildrenInForm() == true")
     * @MongoDB\Field(type="boolean")
     */
    private $displayChildrenInForm = true;

    /**
     * @var bool
     * @Exclude(if="object.getShowExpanded() == false")
     * @MongoDB\Field(type="boolean")
     */
    private $showExpanded = false;

    /**
     * @var bool
     * @Exclude(if="object.getUnexpandable() == false")
     * @MongoDB\Field(type="boolean")
     */
    private $unexpandable = false;

    /**
     * @var bool
     * @Exclude
     * If Option is loaded by a fixture
     */
    private $isFixture = false;

    /**
     * @Accessor(getter="getOrderedSubcategories")
     * @Exclude(if="object.getSubcategoriesCount() == 0")
     * @MongoDB\ReferenceMany(targetDocument="App\Document\Category", mappedBy="parent",cascade={"persist", "remove"}, sort={"index"="ASC"})
     */
    private $subcategories;

    /** 
    * OpenStreetMap Tags : { amenity: shop, key: value }
    * @MongoDB\Field(type="hash") 
    */
    private $osmTags = [];

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

    public function __construct()
    {
        $this->subcategories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function allSubcategories()
    {
        $result = [];

        return $result;
    }

    public function getNameWithParent()
    {
        $result = '';
        if ($this->getParentOption()) {
            $result .= $this->getParentOption()->getName().'@';
        }
        $result .= $this->getName();

        return $result;
    }

    public function getParentOption()
    {
        if (!$this->parent) {
            return null;
        }

        return $this->parent->parent;
    }

    public function getParentOptionId()
    {
        $parent = $this->getParentOption();

        return $parent ? $parent->getStringId() : null;
    }

    public function getIdAndParentOptionIds()
    {
        return $this->recursivelyAddParentOptionId($this);
    }

    private function recursivelyAddParentOptionId($option)
    {
        $result = [];
        $parentOption = $option->getParentOption();
        if ($parentOption) {
            $result = $this->recursivelyAddParentOptionId($parentOption);
        }
        $result[] = $option->getId();

        return $result;
    }

    public function getIdAndChildrenOptionIds()
    {
        return $this->recursivelyAddChilrenOptionIds($this);
    }

    private function recursivelyAddChilrenOptionIds($option)
    {
        $result = [$option->getId()];
        foreach ($option->getSubcategories() as $categorie) {
            foreach ($categorie->getOptions() as $childOption) {
                $result = array_merge($result, $this->recursivelyAddChilrenOptionIds($childOption));
            }
        }

        return $result;
    }

    public function getAllSubcategoriesIds()
    {
        return $this->recursivelyGetSubcategoriesIds($this);
    }

    private function recursivelyGetSubcategoriesIds($option)
    {
        $result = [];
        foreach ($option->getSubcategories() as $categorie) {
            $result[] = $categorie->getId();
            foreach ($categorie->getOptions() as $childOption) {
                $result = array_merge($result, $this->recursivelyGetSubcategoriesIds($childOption));
            }
        }

        return $result;
    }

    public function getSubcategoriesCount()
    {
        if ($this->subcategories) {
            return $this->subcategories->count();
        }

        return 0;
    }

    public function getOrderedSubcategories()
    {
        $sortedCategories = is_array($this->subcategories) ? $this->subcategories : $this->subcategories->toArray();
        usort($sortedCategories, function ($a, $b) { return $a->getIndex() - $b->getIndex(); });

        return $sortedCategories;
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

    public function getStringId()
    {
        return strval($this->id);
    }

    public function getCustomStringId()
    {
        return $this->customId ?: strval($this->id);
    }

    public function setId($param = null)
    {
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
     * Add subcategory.
     *
     * @param App\Document\Category $subcategory
     */
    public function addSubcategory(\App\Document\Category $subcategory, $updateParent = true)
    {
        if ($updateParent) {
            $subcategory->setParent($this, false);
        }
        $this->subcategories[] = $subcategory;
    }

    /**
     * Remove subcategory.
     *
     * @param App\Document\Category $subcategory
     */
    public function removeSubcategory(\App\Document\Category $subcategory, $updateParent = true)
    {
        if ($updateParent) {
            $subcategory->setParent(null);
        }
        $this->subcategories->removeElement($subcategory);
    }

    /**
     * Get subcategories.
     *
     * @return \Doctrine\Common\Collections\Collection $subcategories
     */
    public function getSubcategories()
    {
        return $this->subcategories;
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
     * Set color.
     *
     * @param string $color
     *
     * @return $this
     */
    public function setColor($color)
    {
        if (6 == strlen($color)) {
            $color = '#'.$color;
        }
        $this->color = $color;

        return $this;
    }

    /**
     * Get color.
     *
     * @return string $color
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set color.
     *
     * @param string $color
     *
     * @return $this
     */
    public function setSoftColor($color)
    {
        if (6 == strlen($color)) {
            $color = '#'.$color;
        }
        $this->softColor = $color;

        return $this;
    }

    /**
     * Get color.
     *
     * @return string $color
     */
    public function getSoftColor()
    {
        return $this->softColor;
    }

    /**
     * Set icon.
     *
     * @param string $icon
     *
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon.
     *
     * @return string $icon
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set textHelper.
     *
     * @param string $textHelper
     *
     * @return $this
     */
    public function setTextHelper($textHelper)
    {
        $this->textHelper = $textHelper;

        return $this;
    }

    /**
     * Get textHelper.
     *
     * @return string $textHelper
     */
    public function getTextHelper()
    {
        return $this->textHelper;
    }

    /**
     * Set useIconForMarker.
     *
     * @param bool $useIconForMarker
     *
     * @return $this
     */
    public function setUseIconForMarker($useIconForMarker)
    {
        $this->useIconForMarker = $useIconForMarker;

        return $this;
    }

    /**
     * Get useIconForMarker.
     *
     * @return bool $useIconForMarker
     */
    public function getUseIconForMarker()
    {
        return $this->useIconForMarker;
    }

    /**
     * Set useColorForMarker.
     *
     * @param bool $useColorForMarker
     *
     * @return $this
     */
    public function setUseColorForMarker($useColorForMarker)
    {
        $this->useColorForMarker = $useColorForMarker;

        return $this;
    }

    /**
     * Get useColorForMarker.
     *
     * @return bool $useColorForMarker
     */
    public function getUseColorForMarker()
    {
        return $this->useColorForMarker;
    }

    /**
     * Set showOpenHours.
     *
     * @param bool $showOpenHours
     *
     * @return $this
     */
    public function setShowOpenHours($showOpenHours)
    {
        $this->showOpenHours = $showOpenHours;

        return $this;
    }

    /**
     * Get showOpenHours.
     *
     * @return bool $showOpenHours
     */
    public function getShowOpenHours()
    {
        return $this->showOpenHours;
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
     * Set parent.
     *
     * @param App\Document\Category $parent
     *
     * @return $this
     */
    public function setParent(\App\Document\Category $parent)
    {
        if ($parent && in_array($parent->getId(), $this->getAllSubcategoriesIds())) {
            // Circular reference
        } else {
            $this->parent = $parent;
        }

        return $this;
    }

    /**
     * Get parent.
     *
     * @return App\Document\Category $parent
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
     * Set disableInInfoBar.
     *
     * @param bool $disableInInfoBar
     *
     * @return $this
     */
    public function setDisableInInfoBar($disableInInfoBar)
    {
        $this->disableInInfoBar = $disableInInfoBar;

        return $this;
    }

    /**
     * Get disableInInfoBar.
     *
     * @return bool $disableInInfoBar
     */
    public function getDisableInInfoBar()
    {
        return $this->disableInInfoBar;
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
     * Set displayChildrenInMenu.
     *
     * @param bool $displayChildrenInMenu
     *
     * @return $this
     */
    public function setDisplayChildrenInMenu($displayChildrenInMenu)
    {
        $this->displayChildrenInMenu = $displayChildrenInMenu;

        return $this;
    }

    /**
     * Get displayChildrenInMenu.
     *
     * @return bool $displayChildrenInMenu
     */
    public function getDisplayChildrenInMenu()
    {
        return $this->displayChildrenInMenu;
    }

    /**
     * Set displayChildrenInInfoBar.
     *
     * @param bool $displayChildrenInInfoBar
     *
     * @return $this
     */
    public function setDisplayChildrenInInfoBar($displayChildrenInInfoBar)
    {
        $this->displayChildrenInInfoBar = $displayChildrenInInfoBar;

        return $this;
    }

    /**
     * Get displayChildrenInInfoBar.
     *
     * @return bool $displayChildrenInInfoBar
     */
    public function getDisplayChildrenInInfoBar()
    {
        return $this->displayChildrenInInfoBar;
    }

    /**
     * Set displayChildrenInForm.
     *
     * @param bool $displayChildrenInForm
     *
     * @return $this
     */
    public function setDisplayChildrenInForm($displayChildrenInForm)
    {
        $this->displayChildrenInForm = $displayChildrenInForm;

        return $this;
    }

    /**
     * Get displayChildrenInForm.
     *
     * @return bool $displayChildrenInForm
     */
    public function getDisplayChildrenInForm()
    {
        return $this->displayChildrenInForm;
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
     * Get the value of osmTags
     */ 
    public function getOsmTags()
    {
        return $this->osmTags;
    }

    /**
     * Set the value of osmTags
     *
     * @return  self
     */ 
    public function setOsmTags($osmTags)
    {
        if (is_string($osmTags)) {
            $osmTags = (array) json_decode($osmTags);
        }
        $this->osmTags = $osmTags;
        return $this;
    }

    public function setOsmTag($key, $value)
    {
        $this->osmTags[$key] = $value;
        return $this;
    }

    public function getOsmTagsStringified() {
        $result = "";
        foreach($this->getOsmTags() as $key => $value) {
            $result .= "[$key=$value]";
        }
        return $result;
    }

    /**
     * Get the value of enableDescription
     *
     * @return  bool
     */ 
    public function getEnableDescription()
    {
        return $this->enableDescription;
    }

    /**
     * Set the value of enableDescription
     *
     * @param  bool  $enableDescription
     *
     * @return  self
     */ 
    public function setEnableDescription(bool $enableDescription)
    {
        $this->enableDescription = $enableDescription;

        return $this;
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
