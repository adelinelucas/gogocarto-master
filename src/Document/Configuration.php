<?php

namespace App\Document;

use App\Document\Configuration\ConfigurationApi;
use App\Document\Configuration\ConfigurationHome;
use App\Document\Configuration\ConfigurationInfobar;
use App\Document\Configuration\ConfigurationMarker;
use App\Document\Configuration\ConfigurationMenu;
use App\Document\Configuration\ConfigurationUser;
use App\Document\Configuration\ConfigurationSaas;
use App\Document\Configuration\ConfigurationOsm;
use App\Document\Configuration\ConfigurationDuplicates;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use OzdemirBurak\Iris\Color\Hex;
use OzdemirBurak\Iris\Color\Rgba;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Helper\GoGoHelper;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PostLoad;

/**
 * Main Configuration.
 * 
 * @HasLifecycleCallbacks
 * @MongoDB\Document(repositoryClass="App\Repository\ConfigurationRepository")
 */
class Configuration implements \JsonSerializable
{
    /** @MongoDB\Id(strategy="INCREMENT") */
    private $id;

    /** @MongoDB\Field(type="string") */
    protected $dbName;

    /** @MongoDB\Field(type="string") */
    protected $customDomain = null;

    /** @MongoDB\Field(type="string") */
    protected $locale = "fr";

    // ----------------------------
    // --------- BASICS -----------
    // ----------------------------

    /** @MongoDB\Field(type="string") */
    protected $appName;

    /**
     * @MongoDB\Field(type="string")
     * @Gedmo\Slug(fields={"appName"}, updatable=false)
     */
    protected $appSlug;

    /** @MongoDB\Field(type="string") */
    protected $appBaseline;

    /** @MongoDB\Field(type="string") */
    // For meta keywords header
    protected $appTags;

    /**
    * Only for SAAS mode. Make this project visible on the SAAS home page (=project list)
    * @MongoDB\Field(type="bool")
    */
    protected $publishOnSaasPage = true;

    /** @MongoDB\Field(type="string") */
    protected $dataLicenseUrl = 'https://opendatacommons.org/licenses/odbl/summary/';

    // ----------------------------
    // --------- IMAGES -----------
    // ----------------------------

    /** @MongoDB\ReferenceOne(targetDocument="App\Document\ConfImage", cascade={"all"})   */
    protected $logo;

    /** @MongoDB\ReferenceOne(targetDocument="App\Document\ConfImage", cascade={"all"})   */
    protected $logoInline;

    /** @MongoDB\ReferenceOne(targetDocument="App\Document\ConfImage", cascade={"all"})   */
    protected $socialShareImage;

    /** @MongoDB\ReferenceOne(targetDocument="App\Document\ConfImage", cascade={"all"})   */
    protected $favicon;

    // ----------------------------
    // ---------- TEXTS -----------
    // ----------------------------
    // The strings to describe an element of the directory (it can be a "point", an "organization", a "person" ...)

    /** @MongoDB\Field(type="string") */
    protected $elementDisplayName = '';

    /** @MongoDB\Field(type="string") */
    protected $elementDisplayNameDefinite = ""; 

    /** @MongoDB\Field(type="string") */
    protected $elementDisplayNameIndefinite = '';

    /** @MongoDB\Field(type="string") */
    protected $elementDisplayNamePlural = ''; 

    // -----------------------------
    // ----------- PWA -------------
    // -----------------------------


    /** @MongoDB\Field(type="string") */
    protected $appNameShort;

    /** @MongoDB\Field(type="string") */
    protected $packageName;

    /** @MongoDB\Field(type="string") */
    protected $sha256CertFingerprints;

    /** @MongoDB\Field(type="bool") */
    protected $hideHeaderInPwa = true;

    // -----------------------------
    // --------- GENERAL -----------
    // -----------------------------

    /** @MongoDB\Field(type="bool") */
    protected $activateHomePage;

    /** @MongoDB\Field(type="bool") */
    protected $activatePartnersPage;

    /** @MongoDB\Field(type="string") */
    protected $partnerPageTitle;

    /** @MongoDB\Field(type="bool") */
    protected $activateAbouts;

    /** @MongoDB\Field(type="string") */
    protected $aboutHeaderTitle;

    // ----------------------------
    // ---------- USER -----------
    // ----------------------------

    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationUser") */
    protected $user;

    // ----------------------------
    // ----------- HOME -----------
    // ----------------------------

    /** @MongoDB\ReferenceOne(targetDocument="App\Document\ConfImage", cascade={"all"})   */
    protected $backgroundImage;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationHome") */
    protected $home;

    // ----------------------------
    // --------- FEATURES ---------
    // ----------------------------

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $favoriteFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $shareFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $exportIframeFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $directionsFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $reportFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $stampFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $pendingFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $customPopupFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $listModeFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $searchPlaceFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $searchGeolocateFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $searchElementsFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $searchCategoriesFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $layersFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $mapDefaultViewFeature;

    /** @MongoDB\Field(type="string") */
    protected $searchExcludingWords;

    // ---------------------------------
    // --------- CONTRIBUTIONS ---------
    // ---------------------------------

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $addFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $editFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $deleteFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $collaborativeModerationFeature;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\FeatureConfiguration") */
    protected $directModerationFeature;

    /** @MongoDB\Field(type="int") */
    protected $minVoteToForceChangeStatus = 10;

    /** @MongoDB\Field(type="int") */
    protected $minVoteToChangeStatus = 5;

    /** @MongoDB\Field(type="int") */
    protected $maxOppositeVoteTolerated = 0;

    /** @MongoDB\Field(type="int") */
    protected $minDayBetweenContributionAndCollaborativeValidation = 2;

    /** @MongoDB\Field(type="int") */
    protected $maxDaysLeavingAnElementPending = 15;

    /** @MongoDB\Field(type="string") */
    protected $collaborativeModerationExplanations;

    // -------------------------
    // --------- MAP -----------
    // -------------------------

    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationMenu") */
    protected $menu;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationInfobar") */
    protected $infobar;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationMarker") */
    protected $marker;

    /** @MongoDB\ReferenceOne(targetDocument="App\Document\TileLayer") */
    protected $defaultTileLayer;

    /** @MongoDB\Field(type="float") */
    protected $defaultNorthEastBoundsLat;

    /** @MongoDB\Field(type="float") */
    protected $defaultNorthEastBoundsLng;

    /** @MongoDB\Field(type="float") */
    protected $defaultSouthWestBoundsLat;

    /** @MongoDB\Field(type="float") */
    protected $defaultSouthWestBoundsLng;

    /** @MongoDB\Field(type="bool") */
    protected $saveViewportInCookies = true;

    /** @MongoDB\Field(type="bool") */
    protected $saveTileLayerInCookies = true;

    // -------------------------
    // ------ MAP POPUP --------
    // -------------------------

    /** @MongoDB\Field(type="string") */
    protected $customPopupText;

    /** @MongoDB\Field(type="int") */
    protected $customPopupId = 0;

    /** @MongoDB\Field(type="bool") */
    protected $customPopupShowOnlyOnce;

    // ----------------------------
    // ---------- MAILS -----------
    // ----------------------------

    // for elements

    /** @MongoDB\EmbedOne(targetDocument="App\Document\AutomatedMailConfiguration") */
    protected $addMail;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\AutomatedMailConfiguration") */
    protected $editMail;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\AutomatedMailConfiguration") */
    protected $deleteMail;

    // for contributors

    /** @MongoDB\EmbedOne(targetDocument="App\Document\AutomatedMailConfiguration") */
    protected $validationMail;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\AutomatedMailConfiguration") */
    protected $refusalMail;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\AutomatedMailConfiguration") */
    protected $reportResolvedMail;

    /** @MongoDB\EmbedOne(targetDocument="App\Document\AutomatedMailConfiguration") */
    protected $newsletterMail;

    // ----------------------------
    // ---------- FORM ------------
    // ----------------------------

    /** @MongoDB\Field(type="string") */
    protected $elementFormIntroText;

    /** @MongoDB\Field(type="string") */
    protected $elementFormValidationText;

    /** @MongoDB\Field(type="string") */
    protected $elementFormOwningText;

    /** @MongoDB\Field(type="string") */
    protected $elementFormGeocodingHelp;

    /** @MongoDB\Field(type="string") */
    protected $elementFormFieldsJson = "";

    // ----------------------------
    // -------- IMPORTS -----------
    // ----------------------------

    /** @MongoDB\Field(type="string") */
    protected $fontImport;

    /** @MongoDB\Field(type="string") */
    protected $iconImport;

    // -------------------------
    // --------- STYLE ---------
    // -------------------------

    /** @MongoDB\Field(type="string") */
    protected $theme;

    // FONTS

    /** @MongoDB\Field(type="string") */
    protected $mainFont;

    /** @MongoDB\Field(type="string") */
    protected $titleFont;

    // COLORS BASIC

    /** @MongoDB\Field(type="string") */
    protected $textColor;

    /** @MongoDB\Field(type="string") */
    protected $primaryColor;

    /** @MongoDB\Field(type="string") */
    protected $secondaryColor;

    /** @MongoDB\Field(type="string") */
    protected $backgroundColor;

    /** @MongoDB\Field(type="string") */
    protected $homeBackgroundColor;

    // COLORS INTERMEDIAITE

    /** @MongoDB\Field(type="string") */
    protected $textDarkColor;

    /** @MongoDB\Field(type="string") */
    protected $textDarkSoftColor;

    /** @MongoDB\Field(type="string") */
    protected $textLightColor;

    /** @MongoDB\Field(type="string") */
    protected $textLightSoftColor;

    /** @MongoDB\Field(type="string") */
    protected $contentBackgroundColor;

    /** @MongoDB\Field(type="string") */
    protected $contentBackgroundElementBodyColor;

    /** @MongoDB\Field(type="string") */
    protected $headerColor;

    /** @MongoDB\Field(type="string") */
    protected $headerTextColor;

    /** @MongoDB\Field(type="string") */
    protected $headerHoverColor;

    // COLORS ADVANCED

    /** @MongoDB\Field(type="string") */
    protected $searchBarColor;

    /** @MongoDB\Field(type="string") */
    protected $disableColor;

    /** @MongoDB\Field(type="string") */
    protected $errorColor;

    /** @MongoDB\Field(type="string") */
    protected $pendingColor;

    /** @MongoDB\Field(type="string") */
    protected $interactiveSectionColor;

    // -------------------------
    // ---- CUSTOM ASSETS ------
    // -------------------------

    /** @MongoDB\Field(type="string") */
    protected $customCSS = '';

    /** @MongoDB\Field(type="string") */
    protected $customJavascript = '';

    // -------------------------
    // ---- CUSTOM ASSETS ------
    // -------------------------
    /** @MongoDB\Field(type="string") */
    protected $customDashboard = '';

    // -------------------------
    // --------- API -----------
    // -------------------------

    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationApi") */
    protected $api;

    // -------------------------
    // --------- OSM -----------
    // -------------------------

    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationOsm") */
    protected $osm;
    
    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationDuplicates") */
    protected $duplicates;

    // ----------------------------
    // ---------- SAAS ------------
    // ----------------------------

    /** @MongoDB\EmbedOne(targetDocument="App\Document\Configuration\ConfigurationSaas") */
    protected $saas;

    public function __construct()
    {
        $this->addMail = new AutomatedMailConfiguration();
        $this->editMail = new AutomatedMailConfiguration();
        $this->deleteMail = new AutomatedMailConfiguration();
        $this->validationMail = new AutomatedMailConfiguration();
        $this->refusalMail = new AutomatedMailConfiguration();
        $this->reportResolvedMail = new AutomatedMailConfiguration();
        $this->newsletterMail = new AutomatedMailConfiguration();

        $this->favoriteFeature = new FeatureConfiguration();
        $this->shareFeature = new FeatureConfiguration();
        $this->exportIframeFeature = new FeatureConfiguration();
        $this->directionsFeature = new FeatureConfiguration();
        $this->reportFeature = new FeatureConfiguration();
        $this->stampFeature = new FeatureConfiguration();
        $this->pendingFeature = new FeatureConfiguration();
        $this->listModeFeature = new FeatureConfiguration();
        $this->searchPlaceFeature = new FeatureConfiguration();
        $this->searchElementsFeature = new FeatureConfiguration();
        $this->searchCategoriesFeature = new FeatureConfiguration();
        $this->searchGeolocateFeature = new FeatureConfiguration();
        $this->layersFeature = new FeatureConfiguration();
        $this->mapDefaultViewFeature = new FeatureConfiguration();

        $this->addFeature = new FeatureConfiguration();
        $this->editFeature = new FeatureConfiguration();
        $this->deleteFeature = new FeatureConfiguration();
        $this->collaborativeModerationFeature = new FeatureConfiguration();
        $this->directModerationFeature = new FeatureConfiguration();

        $this->user = new ConfigurationUser();
        $this->menu = new ConfigurationMenu();
        $this->infobar = new ConfigurationInfobar();
        $this->api = new ConfigurationApi();
        $this->osm = new ConfigurationOsm();
        $this->duplicates = new ConfigurationDuplicates();
        $this->home = new ConfigurationHome();
        $this->marker = new ConfigurationMarker();
    }

    /** @PostLoad */
    public function postLoad()
    {
        $this->getDuplicates()->setFormFields($this->getElementFormFields());
    } 

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * List of the fields needed in the gogo compact API : all basic field
     * for the marker to be rendered. I.e lat/lng, infos for filtering, and infos for the markerPopup
     */
    public function getCompactFields()
    {
        $compactFields = [];
        foreach ($this->getMenu()->getFilters() as $filter) {
            if (isset($filter->field)) {
                $compactFields[$filter->field] = $filter->type;
            }
            if (isset($filter->fieldEnd)) {
                $compactFields[$filter->fieldEnd] = $filter->type;
            }
        }
        foreach($this->getMarker()->getFieldsUsedByTemplate() as $field) {
            $compactFields[$field] = 'markerTemplate';
        }
        return $compactFields; 
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        $this->validateTemplateOnlyUsePublicProperties($context, $this->getInfobar()->getHeaderTemplate(), 'infobar.headerTemplate');
        $this->validateTemplateOnlyUsePublicProperties($context, $this->getInfobar()->getBodyTemplate(), 'infobar.bodyTemplate');
        $this->validateTemplateOnlyUsePublicProperties($context, $this->getMarker()->getPopupTemplate(), 'marker.popupTemplate');
    }

    private function validateTemplateOnlyUsePublicProperties($context, $template, $path)
    {
        $fieldsUsed = GoGoHelper::extractFieldsUsedInTemplate($template);
        $privateProps = $this->getApi()->getPublicApiPrivateProperties();
        $privateFieldsUsed = array_intersect($fieldsUsed, $privateProps);
        // the email field can be here because it will be replaced by a button to send email
        $privateFieldsUsed = array_diff($privateFieldsUsed, ['email']);
        if (count($privateFieldsUsed) > 0) {
            $fieldsList = strtoupper(implode(', ', $privateFieldsUsed));
            $context->buildViolation($this->t('error.fields_not_shared', [ 'fieldsList' => $fieldsList ]))
                ->atPath($path)
                ->addViolation()
            ;
        }

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

    public function getColor($colorString)
    {
        try {
            if (false !== strpos($colorString, '#')) {
                return new Hex($colorString);
            } else {
                return new Rgba($colorString);
            }
        } catch (\Exception $e) {
            return new Hex('#000');
        }
    }

    public function getDefaultColor($colorName)
    {
        $method = 'getDefault'.ucfirst($colorName);
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return null;
        }
    }

    public function getDefaultBounds()
    {
        return [[$this->defaultNorthEastBoundsLat, $this->defaultNorthEastBoundsLng], [$this->defaultSouthWestBoundsLat, $this->defaultSouthWestBoundsLng]];
    }

    public function getElementFormFields()
    {
        return json_decode($this->getElementFormFieldsJson());
    }

    // Ex: [ 'name' => [ 'type' => 'text', 'label' => 'A title'] ]
    public function getElementFormFieldsMapping()
    {
        $result = [];
        foreach ($this->getElementFormFields() as $field) {
            if (isset($field->name)) $result[$field->name] = $field;
        }
        return $result;
    }

    /* --------------------------------------- */
    /*              DEFAULT COLORS
    /* ---------------------------------------- */

    public function getDefaultSecondaryColor()
    {
        return $this->secondaryColor ? $this->secondaryColor : $this->primaryColor;
    }

    public function getDefaultBackgroundColor()
    {
        return $this->backgroundColor ? $this->backgroundColor : '#ffffff';
    }

    public function getDefaultHeaderColor()
    {
        return $this->headerColor ? $this->headerColor : ('transiscope' == $this->theme ? '#ffffff' : $this->getDefaultTextDarkColor());
    }

    public function getDefaultDisableColor()
    {
        if ($this->disableColor) {
            return $this->disableColor;
        }
        $textColor = $this->getColor($this->getDefaultTextContentColor());

        return $textColor->isDark() ? $textColor->lighten(40)->__toString() : $textColor->darken(40)->__toString();
    }

    public function getDefaultSearchBarColor()
    {
        return $this->searchBarColor ? $this->searchBarColor : ('transiscope' == $this->theme ? $this->getDefaultTextDarkColor() : $this->primaryColor);
    }

    public function getDefaultPendingColor()
    {
        return $this->pendingColor ? $this->pendingColor : '#555555';
    }

    public function getDefaultInteractiveSectionColor()
    {
        return $this->interactiveSectionColor ? $this->interactiveSectionColor : $this->primaryColor;
    }

    public function getDefaultContentBackgroundColor()
    {
        return $this->contentBackgroundColor ? $this->contentBackgroundColor : '#ffffff';
    }

    public function getDefaultContentBackgroundElementBodyColor()
    {
        return $this->contentBackgroundElementBodyColor ? $this->contentBackgroundElementBodyColor : $this->getDefaultBackgroundColor();
    }

    public function getDefaultContentBackgroundSoftColor()
    {
        $content = $this->getColor($this->getDefaultContentBackgroundColor());
        $background = $this->getColor($this->getDefaultBackgroundColor());
        if ($content->isDark()) {
            return $background->isDark() ? $background : $content->lighten(10);
        } else {
            return $background->isLight() ? $background : $content->darken(10);
        }
    }

    public function getDefaultTextDarkColor()
    {
        if ($this->textDarkColor) {
            return $this->textDarkColor;
        }
        $textColor = $this->getColor($this->textColor);

        return $textColor->isDark() ? $this->textColor : '#272727';
    }

    public function getDefaultTextDarkSoftColor()
    {
        if ($this->textDarkSoftColor) {
            return $this->textDarkSoftColor;
        }
        $color = $this->getColor($this->getDefaultTextDarkColor());

        return $color->lighten(15)->__toString();
    }

    public function getDefaultTextLightColor()
    {
        if ($this->textLightColor) {
            return $this->textLightColor;
        }
        $textColor = $this->getColor($this->textColor);

        return $textColor->isLight() ? $this->textColor : '#ffffff';
    }

    public function getDefaultTextLightSoftColor()
    {
        if ($this->textLightSoftColor) {
            return $this->textLightSoftColor;
        }
        $color = $this->getColor($this->getDefaultTextLightColor());

        return $color->darken(15)->__toString();
    }

    public function getDefaultHeaderTextColor()
    {
        if ($this->headerTextColor) {
            return $this->headerTextColor;
        }
        $headerBgd = $this->getColor($this->getDefaultHeaderColor());

        return $headerBgd->isLight() ? $this->getDefaultTextDarkColor() : $this->getDefaultTextLightColor();
    }

    public function getDefaultHeaderHoverColor()
    {
        return $this->headerHoverColor ? $this->headerHoverColor : $this->primaryColor;
    }

    public function getDefaultTextContentColor()
    {
        $contentBgd = $this->getColor($this->getDefaultContentBackgroundColor());

        return $contentBgd->isLight() ? $this->getDefaultTextDarkColor() : $this->getDefaultTextLightColor();
    }

    public function getDefaultTextSoftContentColor()
    {
        $contentBgd = $this->getColor($this->getDefaultContentBackgroundColor());

        return $contentBgd->isLight() ? $this->getDefaultTextDarkSoftColor() : $this->getDefaultTextLightSoftColor();
    }

    public function getDefaultErrorColor()
    {
        return $this->errorColor ? $this->errorColor : '#B90303';
    }

    public function getDefaultModalBackgroundColor()
    {
        $contentBgd = $this->getColor($this->getDefaultContentBackgroundColor());

        return $contentBgd->isDark() ? $this->getDefaultContentBackgroundColor() : $this->getDefaultTextDarkColor();
    }

    public function getDefaultHomeBackgroundColor()
    {
        return $this->homeBackgroundColor ? $this->homeBackgroundColor : $this->getDefaultBackgroundColor();
    }

    /* --------------------------------------- */
    /*             END DEFAULT COLORS
    /* ---------------------------------------- */

    /**
     * Set favoriteFeature.
     *
     * @param App\Document\FeatureConfiguration $favoriteFeature
     *
     * @return $this
     */
    public function setFavoriteFeature(\App\Document\FeatureConfiguration $favoriteFeature)
    {
        $this->favoriteFeature = $favoriteFeature;

        return $this;
    }

    /**
     * Get favoriteFeature.
     *
     * @return App\Document\FeatureConfiguration $favoriteFeature
     */
    public function getFavoriteFeature()
    {
        return $this->favoriteFeature;
    }

    /**
     * Set shareFeature.
     *
     * @param App\Document\FeatureConfiguration $shareFeature
     *
     * @return $this
     */
    public function setShareFeature(\App\Document\FeatureConfiguration $shareFeature)
    {
        $this->shareFeature = $shareFeature;

        return $this;
    }

    /**
     * Get shareFeature.
     *
     * @return App\Document\FeatureConfiguration $shareFeature
     */
    public function getShareFeature()
    {
        return $this->shareFeature;
    }

    /**
     * Set exportIframeFeature.
     *
     * @param App\Document\FeatureConfiguration $exportIframeFeature
     *
     * @return $this
     */
    public function setExportIframeFeature(\App\Document\FeatureConfiguration $exportIframeFeature)
    {
        $this->exportIframeFeature = $exportIframeFeature;

        return $this;
    }

    /**
     * Get exportIframeFeature.
     *
     * @return App\Document\FeatureConfiguration $exportIframeFeature
     */
    public function getExportIframeFeature()
    {
        return $this->exportIframeFeature;
    }

    /**
     * Set directionsFeature.
     *
     * @param App\Document\FeatureConfiguration $directionsFeature
     *
     * @return $this
     */
    public function setDirectionsFeature(\App\Document\FeatureConfiguration $directionsFeature)
    {
        $this->directionsFeature = $directionsFeature;

        return $this;
    }

    /**
     * Get directionsFeature.
     *
     * @return App\Document\FeatureConfiguration $directionsFeature
     */
    public function getDirectionsFeature()
    {
        return $this->directionsFeature;
    }

    /**
     * Set reportFeature.
     *
     * @param App\Document\FeatureConfiguration $reportFeature
     *
     * @return $this
     */
    public function setReportFeature(\App\Document\FeatureConfiguration $reportFeature)
    {
        $this->reportFeature = $reportFeature;

        return $this;
    }

    /**
     * Get reportFeature.
     *
     * @return App\Document\FeatureConfiguration $reportFeature
     */
    public function getReportFeature()
    {
        return $this->reportFeature;
    }

    /**
     * Set pendingFeature.
     *
     * @param App\Document\FeatureConfiguration $pendingFeature
     *
     * @return $this
     */
    public function setPendingFeature(\App\Document\FeatureConfiguration $pendingFeature)
    {
        $this->pendingFeature = $pendingFeature;

        return $this;
    }

    /**
     * Get pendingFeature.
     *
     * @return App\Document\FeatureConfiguration $pendingFeature
     */
    public function getPendingFeature()
    {
        return $this->pendingFeature;
    }

    /**
     * Set addFeature.
     *
     * @param App\Document\FeatureConfiguration $addFeature
     *
     * @return $this
     */
    public function setAddFeature(\App\Document\FeatureConfiguration $addFeature)
    {
        $this->addFeature = $addFeature;

        return $this;
    }

    /**
     * Get addFeature.
     *
     * @return App\Document\FeatureConfiguration $addFeature
     */
    public function getAddFeature()
    {
        return $this->addFeature;
    }

    /**
     * Set editFeature.
     *
     * @param App\Document\FeatureConfiguration $editFeature
     *
     * @return $this
     */
    public function setEditFeature(\App\Document\FeatureConfiguration $editFeature)
    {
        $this->editFeature = $editFeature;

        return $this;
    }

    /**
     * Get editFeature.
     *
     * @return App\Document\FeatureConfiguration $editFeature
     */
    public function getEditFeature()
    {
        return $this->editFeature;
    }

    /**
     * Set deleteFeature.
     *
     * @param App\Document\FeatureConfiguration $deleteFeature
     *
     * @return $this
     */
    public function setDeleteFeature(\App\Document\FeatureConfiguration $deleteFeature)
    {
        $this->deleteFeature = $deleteFeature;

        return $this;
    }

    /**
     * Get deleteFeature.
     *
     * @return App\Document\FeatureConfiguration $deleteFeature
     */
    public function getDeleteFeature()
    {
        return $this->deleteFeature;
    }

    /**
     * Set collaborativeModeration.
     *
     * @param App\Document\FeatureConfiguration $collaborativeModeration
     *
     * @return $this
     */
    public function setCollaborativeModeration(\App\Document\FeatureConfiguration $collaborativeModeration)
    {
        $this->collaborativeModeration = $collaborativeModeration;

        return $this;
    }

    /**
     * Get collaborativeModeration.
     *
     * @return App\Document\FeatureConfiguration $collaborativeModeration
     */
    public function getCollaborativeModeration()
    {
        return $this->collaborativeModeration;
    }

    /**
     * Set directModeration.
     *
     * @param App\Document\FeatureConfiguration $directModeration
     *
     * @return $this
     */
    public function setDirectModeration(\App\Document\FeatureConfiguration $directModeration)
    {
        $this->directModeration = $directModeration;

        return $this;
    }

    /**
     * Get directModeration.
     *
     * @return App\Document\FeatureConfiguration $directModeration
     */
    public function getDirectModeration()
    {
        return $this->directModeration;
    }

    /**
     * Set minVoteToChangeStatus.
     *
     * @param int $minVoteToChangeStatus
     *
     * @return $this
     */
    public function setMinVoteToChangeStatus($minVoteToChangeStatus)
    {
        $this->minVoteToChangeStatus = $minVoteToChangeStatus;

        return $this;
    }

    /**
     * Get minVoteToChangeStatus.
     *
     * @return int $minVoteToChangeStatus
     */
    public function getMinVoteToChangeStatus()
    {
        return $this->minVoteToChangeStatus;
    }

    /**
     * Set maxOppositeVoteTolerated.
     *
     * @param int $maxOppositeVoteTolerated
     *
     * @return $this
     */
    public function setMaxOppositeVoteTolerated($maxOppositeVoteTolerated)
    {
        $this->maxOppositeVoteTolerated = $maxOppositeVoteTolerated;

        return $this;
    }

    /**
     * Get maxOppositeVoteTolerated.
     *
     * @return int $maxOppositeVoteTolerated
     */
    public function getMaxOppositeVoteTolerated()
    {
        return $this->maxOppositeVoteTolerated;
    }

    /**
     * Set minDayBetweenContributionAndCollaborativeValidation.
     *
     * @param int $minDayBetweenContributionAndCollaborativeValidation
     *
     * @return $this
     */
    public function setMinDayBetweenContributionAndCollaborativeValidation($minDayBetweenContributionAndCollaborativeValidation)
    {
        $this->minDayBetweenContributionAndCollaborativeValidation = $minDayBetweenContributionAndCollaborativeValidation;

        return $this;
    }

    /**
     * Get minDayBetweenContributionAndCollaborativeValidation.
     *
     * @return int $minDayBetweenContributionAndCollaborativeValidation
     */
    public function getMinDayBetweenContributionAndCollaborativeValidation()
    {
        return $this->minDayBetweenContributionAndCollaborativeValidation;
    }

    /**
     * Set defaultTileLayer.
     *
     * @param App\Document\TileLayer $defaultTileLayer
     *
     * @return $this
     */
    public function setDefaultTileLayer($defaultTileLayer)
    {
        $this->defaultTileLayer = $defaultTileLayer;

        return $this;
    }

    /**
     * Get defaultTileLayer.
     *
     * @return App\Document\TileLayer $defaultTileLayer
     */
    public function getDefaultTileLayer()
    {
        if ($this->defaultTileLayer == null) {
            $tileLayer = new TileLayer();
            $tileLayer->setName('cartodb');
            $tileLayer->setUrl('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png');
            $tileLayer->setAttribution('&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>');
            return $tileLayer;
        }
        return $this->defaultTileLayer;
    }

    /**
     * Set defaultNorthEastBoundsLat.
     *
     * @param float $defaultNorthEastBoundsLat
     *
     * @return $this
     */
    public function setDefaultNorthEastBoundsLat($defaultNorthEastBoundsLat)
    {
        $this->defaultNorthEastBoundsLat = $defaultNorthEastBoundsLat;

        return $this;
    }

    /**
     * Get defaultNorthEastBoundsLat.
     *
     * @return float $defaultNorthEastBoundsLat
     */
    public function getDefaultNorthEastBoundsLat()
    {
        return $this->defaultNorthEastBoundsLat;
    }

    /**
     * Set defaultNorthEastBoundsLng.
     *
     * @param float $defaultNorthEastBoundsLng
     *
     * @return $this
     */
    public function setDefaultNorthEastBoundsLng($defaultNorthEastBoundsLng)
    {
        $this->defaultNorthEastBoundsLng = $defaultNorthEastBoundsLng;

        return $this;
    }

    /**
     * Get defaultNorthEastBoundsLng.
     *
     * @return float $defaultNorthEastBoundsLng
     */
    public function getDefaultNorthEastBoundsLng()
    {
        return $this->defaultNorthEastBoundsLng;
    }

    /**
     * Set defaultSouthWestBoundsLat.
     *
     * @param float $defaultSouthWestBoundsLat
     *
     * @return $this
     */
    public function setDefaultSouthWestBoundsLat($defaultSouthWestBoundsLat)
    {
        $this->defaultSouthWestBoundsLat = $defaultSouthWestBoundsLat;

        return $this;
    }

    /**
     * Get defaultSouthWestBoundsLat.
     *
     * @return float $defaultSouthWestBoundsLat
     */
    public function getDefaultSouthWestBoundsLat()
    {
        return $this->defaultSouthWestBoundsLat;
    }

    /**
     * Set defaultSouthWestBoundsLng.
     *
     * @param float $defaultSouthWestBoundsLng
     *
     * @return $this
     */
    public function setDefaultSouthWestBoundsLng($defaultSouthWestBoundsLng)
    {
        $this->defaultSouthWestBoundsLng = $defaultSouthWestBoundsLng;

        return $this;
    }

    /**
     * Get defaultSouthWestBoundsLng.
     *
     * @return float $defaultSouthWestBoundsLng
     */
    public function getDefaultSouthWestBoundsLng()
    {
        return $this->defaultSouthWestBoundsLng;
    }

    /**
     * Set primaryColor.
     *
     * @param string $primaryColor
     *
     * @return $this
     */
    public function setPrimaryColor($primaryColor)
    {
        if (6 == strlen($primaryColor)) {
            $primaryColor = '#'.$primaryColor;
        }
        $this->primaryColor = $primaryColor;

        return $this;
    }

    /**
     * Get primaryColor.
     *
     * @return string $primaryColor
     */
    public function getPrimaryColor()
    {
        return $this->primaryColor;
    }

    /**
     * Set secondaryColor.
     *
     * @param string $secondaryColor
     *
     * @return $this
     */
    public function setSecondaryColor($secondaryColor)
    {
        if (6 == strlen($secondaryColor)) {
            $secondaryColor = '#'.$secondaryColor;
        }
        $this->secondaryColor = $secondaryColor;

        return $this;
    }

    /**
     * Set collaborativeModerationFeature.
     *
     * @param App\Document\FeatureConfiguration $collaborativeModerationFeature
     *
     * @return $this
     */
    public function setCollaborativeModerationFeature(\App\Document\FeatureConfiguration $collaborativeModerationFeature)
    {
        $this->collaborativeModerationFeature = $collaborativeModerationFeature;

        return $this;
    }

    /**
     * Get collaborativeModerationFeature.
     *
     * @return App\Document\FeatureConfiguration $collaborativeModerationFeature
     */
    public function getCollaborativeModerationFeature()
    {
        return $this->collaborativeModerationFeature;
    }

    /**
     * Set directModerationFeature.
     *
     * @param App\Document\FeatureConfiguration $directModerationFeature
     *
     * @return $this
     */
    public function setDirectModerationFeature(\App\Document\FeatureConfiguration $directModerationFeature)
    {
        $this->directModerationFeature = $directModerationFeature;

        return $this;
    }

    /**
     * Get directModerationFeature.
     *
     * @return App\Document\FeatureConfiguration $directModerationFeature
     */
    public function getDirectModerationFeature()
    {
        return $this->directModerationFeature;
    }

    /**
     * Set mainFont.
     *
     * @param string $mainFont
     *
     * @return $this
     */
    public function setMainFont($mainFont)
    {
        $this->mainFont = $mainFont;

        return $this;
    }

    /**
     * Get mainFont.
     *
     * @return string $mainFont
     */
    public function getMainFont()
    {
        return $this->mainFont;
    }

    /**
     * Set titleFont.
     *
     * @param string $titleFont
     *
     * @return $this
     */
    public function setTitleFont($titleFont)
    {
        $this->titleFont = $titleFont;

        return $this;
    }

    /**
     * Get titleFont.
     *
     * @return string $titleFont
     */
    public function getTitleFont()
    {
        return $this->titleFont ? $this->titleFont : $this->mainFont;
    }

    /**
     * Set customCSS.
     *
     * @param string $customCSS
     *
     * @return $this
     */
    public function setCustomCSS($customCSS)
    {
        $this->customCSS = $customCSS;

        return $this;
    }

    /**
     * Get customCSS.
     *
     * @return string $customCSS
     */
    public function getCustomCSS()
    {
        return $this->customCSS;
    }

    /**
     * Set customJavascript.
     *
     * @param string $customJavascript
     *
     * @return $this
     */
    public function setCustomJavascript($customJavascript)
    {
        $this->customJavascript = $customJavascript;

        return $this;
    }

    /**
     * Get customJavascript.
     *
     * @return string $customJavascript
     */
    public function getCustomJavascript()
    {
        return $this->customJavascript;
    }

    /**
     * Set appName.
     *
     * @param string $appName
     *
     * @return $this
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * Get appName.
     *
     * @return string $appName
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * Set appBaseline.
     *
     * @param string $appBaseline
     *
     * @return $this
     */
    public function setAppBaseline($appBaseline)
    {
        $this->appBaseline = $appBaseline;

        return $this;
    }

    /**
     * Get appBaseline.
     *
     * @return string $appBaseline
     */
    public function getAppBaseline()
    {
        return $this->appBaseline;
    }

    /**
     * Set appTags.
     *
     * @param string $appTags
     *
     * @return $this
     */
    public function setAppTags($appTags)
    {
        $this->appTags = $appTags;

        return $this;
    }

    /**
     * Get appTags.
     *
     * @return string $appTags
     */
    public function getAppTags()
    {
        return $this->appTags;
    }

    /**
     * Set elementDisplayNameDefinite.
     *
     * @param string $elementDisplayNameDefinite
     *
     * @return $this
     */
    public function setElementDisplayNameDefinite($elementDisplayNameDefinite)
    {
        $this->elementDisplayNameDefinite = $elementDisplayNameDefinite;

        return $this;
    }

    /**
     * Get elementDisplayNameDefinite.
     *
     * @return string $elementDisplayNameDefinite
     */
    public function getElementDisplayNameDefinite()
    {
        return $this->elementDisplayNameDefinite;
    }

    /**
     * Set elementDisplayNameIndefinite.
     *
     * @param string $elementDisplayNameIndefinite
     *
     * @return $this
     */
    public function setElementDisplayNameIndefinite($elementDisplayNameIndefinite)
    {
        $this->elementDisplayNameIndefinite = $elementDisplayNameIndefinite;

        return $this;
    }

    /**
     * Get elementDisplayNameIndefinite.
     *
     * @return string $elementDisplayNameIndefinite
     */
    public function getElementDisplayNameIndefinite()
    {
        return $this->elementDisplayNameIndefinite;
    }

    /**
     * Set elementDisplayNamePlural.
     *
     * @param string $elementDisplayNamePlural
     *
     * @return $this
     */
    public function setElementDisplayNamePlural($elementDisplayNamePlural)
    {
        $this->elementDisplayNamePlural = $elementDisplayNamePlural;

        return $this;
    }

    /**
     * Get elementDisplayNamePlural.
     *
     * @return string $elementDisplayNamePlural
     */
    public function getElementDisplayNamePlural()
    {
        return $this->elementDisplayNamePlural;
    }

    /**
     * Set fontImport.
     *
     * @param string $fontImport
     *
     * @return $this
     */
    public function setFontImport($fontImport)
    {
        $this->fontImport = $fontImport;

        return $this;
    }

    /**
     * Get fontImport.
     *
     * @return string $fontImport
     */
    public function getFontImport()
    {
        return $this->fontImport;
    }

    /**
     * Set iconImport.
     *
     * @param string $iconImport
     *
     * @return $this
     */
    public function setIconImport($iconImport)
    {
        $this->iconImport = $iconImport;

        return $this;
    }

    /**
     * Get iconImport.
     *
     * @return string $iconImport
     */
    public function getIconImport()
    {
        return $this->iconImport;
    }

    /**
     * Set elementDisplayName.
     *
     * @param string $elementDisplayName
     *
     * @return $this
     */
    public function setElementDisplayName($elementDisplayName)
    {
        $this->elementDisplayName = $elementDisplayName;

        return $this;
    }

    /**
     * Get elementDisplayName.
     *
     * @return string $elementDisplayName
     */
    public function getElementDisplayName()
    {
        return $this->elementDisplayName;
    }

    /**
     * Set collaborativeModerationExplanations.
     *
     * @param string $collaborativeModerationExplanations
     *
     * @return $this
     */
    public function setCollaborativeModerationExplanations($collaborativeModerationExplanations)
    {
        $this->collaborativeModerationExplanations = $collaborativeModerationExplanations;

        return $this;
    }

    /**
     * Get collaborativeModerationExplanations.
     *
     * @return string $collaborativeModerationExplanations
     */
    public function getCollaborativeModerationExplanations()
    {
        return $this->collaborativeModerationExplanations;
    }

    /**
     * Set appSlug.
     *
     * @param string $appSlug
     *
     * @return $this
     */
    public function setAppSlug($appSlug)
    {
        $this->appSlug = $appSlug;

        return $this;
    }

    /**
     * Get appSlug.
     *
     * @return string $appSlug
     */
    public function getAppSlug()
    {
        return $this->appSlug;
    }

    /**
     * Set addMail.
     *
     * @param App\Document\AutomatedMailConfiguration $addMail
     *
     * @return $this
     */
    public function setAddMail(\App\Document\AutomatedMailConfiguration $addMail)
    {
        $this->addMail = $addMail;

        return $this;
    }

    /**
     * Get addMail.
     *
     * @return App\Document\AutomatedMailConfiguration $addMail
     */
    public function getAddMail()
    {
        return $this->addMail;
    }

    /**
     * Set editMail.
     *
     * @param App\Document\AutomatedMailConfiguration $editMail
     *
     * @return $this
     */
    public function setEditMail(\App\Document\AutomatedMailConfiguration $editMail)
    {
        $this->editMail = $editMail;

        return $this;
    }

    /**
     * Get editMail.
     *
     * @return App\Document\AutomatedMailConfiguration $editMail
     */
    public function getEditMail()
    {
        return $this->editMail;
    }

    /**
     * Set deleteMail.
     *
     * @param App\Document\AutomatedMailConfiguration $deleteMail
     *
     * @return $this
     */
    public function setDeleteMail(\App\Document\AutomatedMailConfiguration $deleteMail)
    {
        $this->deleteMail = $deleteMail;

        return $this;
    }

    /**
     * Get deleteMail.
     *
     * @return App\Document\AutomatedMailConfiguration $deleteMail
     */
    public function getDeleteMail()
    {
        return $this->deleteMail;
    }

    /**
     * Set validationMail.
     *
     * @param App\Document\AutomatedMailConfiguration $validationMail
     *
     * @return $this
     */
    public function setValidationMail(\App\Document\AutomatedMailConfiguration $validationMail)
    {
        $this->validationMail = $validationMail;

        return $this;
    }

    /**
     * Get validationMail.
     *
     * @return App\Document\AutomatedMailConfiguration $validationMail
     */
    public function getValidationMail()
    {
        return $this->validationMail;
    }

    /**
     * Set refusedMail.
     *
     * @param App\Document\AutomatedMailConfiguration $refusedMail
     *
     * @return $this
     */
    public function setRefusedMail(\App\Document\AutomatedMailConfiguration $refusedMail)
    {
        $this->refusedMail = $refusedMail;

        return $this;
    }

    /**
     * Get refusedMail.
     *
     * @return App\Document\AutomatedMailConfiguration $refusedMail
     */
    public function getRefusedMail()
    {
        return $this->refusedMail;
    }

    /**
     * Set refusalMail.
     *
     * @param App\Document\AutomatedMailConfiguration $refusalMail
     *
     * @return $this
     */
    public function setRefusalMail(\App\Document\AutomatedMailConfiguration $refusalMail)
    {
        $this->refusalMail = $refusalMail;

        return $this;
    }

    /**
     * Get refusalMail.
     *
     * @return App\Document\AutomatedMailConfiguration $refusalMail
     */
    public function getRefusalMail()
    {
        return $this->refusalMail;
    }

    /**
     * Set elementFormIntroText.
     *
     * @param string $elementFormIntroText
     *
     * @return $this
     */
    public function setElementFormIntroText($elementFormIntroText)
    {
        $this->elementFormIntroText = $elementFormIntroText;

        return $this;
    }

    /**
     * Get elementFormIntroText.
     *
     * @return string $elementFormIntroText
     */
    public function getElementFormIntroText()
    {
        return $this->elementFormIntroText;
    }

    /**
     * Set elementFormValidationText.
     *
     * @param string $elementFormValidationText
     *
     * @return $this
     */
    public function setElementFormValidationText($elementFormValidationText)
    {
        $this->elementFormValidationText = $elementFormValidationText;

        return $this;
    }

    /**
     * Get elementFormValidationText.
     *
     * @return string $elementFormValidationText
     */
    public function getElementFormValidationText()
    {
        return $this->elementFormValidationText;
    }

    /**
     * Set maxDaysLeavingAnElementPending.
     *
     * @param int $maxDaysLeavingAnElementPending
     *
     * @return $this
     */
    public function setMaxDaysLeavingAnElementPending($maxDaysLeavingAnElementPending)
    {
        $this->maxDaysLeavingAnElementPending = $maxDaysLeavingAnElementPending;

        return $this;
    }

    /**
     * Get maxDaysLeavingAnElementPending.
     *
     * @return int $maxDaysLeavingAnElementPending
     */
    public function getMaxDaysLeavingAnElementPending()
    {
        return $this->maxDaysLeavingAnElementPending;
    }

    /**
     * Set reportResolvedMail.
     *
     * @param App\Document\AutomatedMailConfiguration $reportResolvedMail
     *
     * @return $this
     */
    public function setReportResolvedMail(\App\Document\AutomatedMailConfiguration $reportResolvedMail)
    {
        $this->reportResolvedMail = $reportResolvedMail;

        return $this;
    }

    /**
     * Get reportResolvedMail.
     *
     * @return App\Document\AutomatedMailConfiguration $reportResolvedMail
     */
    public function getReportResolvedMail()
    {
        return $this->reportResolvedMail;
    }

    /**
     * Set minVoteToForceChangeStatus.
     *
     * @param int $minVoteToForceChangeStatus
     *
     * @return $this
     */
    public function setMinVoteToForceChangeStatus($minVoteToForceChangeStatus)
    {
        $this->minVoteToForceChangeStatus = $minVoteToForceChangeStatus;

        return $this;
    }

    /**
     * Get minVoteToForceChangeStatus.
     *
     * @return int $minVoteToForceChangeStatus
     */
    public function getMinVoteToForceChangeStatus()
    {
        return $this->minVoteToForceChangeStatus;
    }

    /**
     * Set customDashboard.
     *
     * @param string $customDashboard
     *
     * @return $this
     */
    public function setCustomDashboard($customDashboard)
    {
        $this->customDashboard = $customDashboard;

        return $this;
    }

    /**
     * Get customDashboard.
     *
     * @return string $customDashboard
     */
    public function getCustomDashboard()
    {
        return $this->customDashboard;
    }

    /**
     * Set elementFormOwningText.
     *
     * @param string $elementFormOwningText
     *
     * @return $this
     */
    public function setElementFormOwningText($elementFormOwningText)
    {
        $this->elementFormOwningText = $elementFormOwningText;

        return $this;
    }

    /**
     * Get elementFormOwningText.
     *
     * @return string $elementFormOwningText
     */
    public function getElementFormOwningText()
    {
        return $this->elementFormOwningText;
    }

    /**
     * Set newsletterMail.
     *
     * @param App\Document\AutomatedMailConfiguration $newsletterMail
     *
     * @return $this
     */
    public function setNewsletterMail(\App\Document\AutomatedMailConfiguration $newsletterMail)
    {
        $this->newsletterMail = $newsletterMail;

        return $this;
    }

    /**
     * Get newsletterMail.
     *
     * @return App\Document\AutomatedMailConfiguration $newsletterMail
     */
    public function getNewsletterMail()
    {
        return $this->newsletterMail;
    }

    /**
     * Set stampFeature.
     *
     * @param App\Document\FeatureConfiguration $stampFeature
     *
     * @return $this
     */
    public function setStampFeature(\App\Document\FeatureConfiguration $stampFeature)
    {
        $this->stampFeature = $stampFeature;

        return $this;
    }

    /**
     * Get stampFeature.
     *
     * @return App\Document\FeatureConfiguration $stampFeature
     */
    public function getStampFeature()
    {
        return $this->stampFeature;
    }

    /**
     * Set customPopupText.
     *
     * @param string $customPopupText
     *
     * @return $this
     */
    public function setCustomPopupText($customPopupText)
    {
        $this->customPopupText = $customPopupText;

        return $this;
    }

    /**
     * Get customPopupText.
     *
     * @return string $customPopupText
     */
    public function getCustomPopupText()
    {
        return $this->customPopupText;
    }

    /**
     * Set customPopupId.
     *
     * @param int $customPopupId
     *
     * @return $this
     */
    public function setCustomPopupId($customPopupId)
    {
        $this->customPopupId = $customPopupId;

        return $this;
    }

    /**
     * Get customPopupId.
     *
     * @return int $customPopupId
     */
    public function getCustomPopupId()
    {
        return $this->customPopupId;
    }

    /**
     * Set customPopupShowOnlyOnce.
     *
     * @param bool $customPopupShowOnlyOnce
     *
     * @return $this
     */
    public function setCustomPopupShowOnlyOnce($customPopupShowOnlyOnce)
    {
        $this->customPopupShowOnlyOnce = $customPopupShowOnlyOnce;

        return $this;
    }

    /**
     * Get customPopupShowOnlyOnce.
     *
     * @return bool $customPopupShowOnlyOnce
     */
    public function getCustomPopupShowOnlyOnce()
    {
        return $this->customPopupShowOnlyOnce;
    }

    /**
     * Set customPopupFeature.
     *
     * @param App\Document\FeatureConfiguration $customPopupFeature
     *
     * @return $this
     */
    public function setCustomPopupFeature(\App\Document\FeatureConfiguration $customPopupFeature)
    {
        $this->customPopupFeature = $customPopupFeature;

        return $this;
    }

    /**
     * Get customPopupFeature.
     *
     * @return App\Document\FeatureConfiguration $customPopupFeature
     */
    public function getCustomPopupFeature()
    {
        return $this->customPopupFeature;
    }

    /**
     * Set elementFormGeocodingHelp.
     *
     * @param string $elementFormGeocodingHelp
     *
     * @return $this
     */
    public function setElementFormGeocodingHelp($elementFormGeocodingHelp)
    {
        $this->elementFormGeocodingHelp = $elementFormGeocodingHelp;

        return $this;
    }

    /**
     * Get elementFormGeocodingHelp.
     *
     * @return string $elementFormGeocodingHelp
     */
    public function getElementFormGeocodingHelp()
    {
        return $this->elementFormGeocodingHelp;
    }

    /**
     * Set activateHomePage.
     *
     * @param bool $activateHomePage
     *
     * @return $this
     */
    public function setActivateHomePage($activateHomePage)
    {
        $this->activateHomePage = $activateHomePage;

        return $this;
    }

    /**
     * Get activateHomePage.
     *
     * @return bool $activateHomePage
     */
    public function getActivateHomePage()
    {
        return $this->activateHomePage;
    }

    /**
     * Set backgroundImage.
     *
     * @param App\Document\ConfImage $backgroundImage
     *
     * @return $this
     */
    public function setBackgroundImage($backgroundImage)
    {
        $this->backgroundImage = $backgroundImage;

        return $this;
    }

    /**
     * Get backgroundImage.
     *
     * @return App\Document\ConfImage $backgroundImage
     */
    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    /**
     * Set displayCategories.
     *
     * @param bool $displayCategories
     *
     * @return $this
     */
    public function setDisplayCategories($displayCategories)
    {
        $this->displayCategories = $displayCategories;

        return $this;
    }

    /**
     * Get displayCategories.
     *
     * @return bool $displayCategories
     */
    public function getDisplayCategories()
    {
        return $this->displayCategories;
    }

    /**
     * Set addElementHint.
     *
     * @param string $addElementHint
     *
     * @return $this
     */
    public function setAddElementHint($addElementHint)
    {
        $this->addElementHint = $addElementHint;

        return $this;
    }

    /**
     * Get addElementHint.
     *
     * @return string $addElementHint
     */
    public function getAddElementHint()
    {
        return $this->addElementHint;
    }

    /**
     * Set seeMoreButton.
     *
     * @param string $seeMoreButton
     *
     * @return $this
     */
    public function setSeeMoreButton($seeMoreButton)
    {
        $this->seeMoreButton = $seeMoreButton;

        return $this;
    }

    /**
     * Get seeMoreButton.
     *
     * @return string $seeMoreButton
     */
    public function getSeeMoreButton()
    {
        return $this->seeMoreButton;
    }

    /**
     * Set home.
     *
     * @param App\Document\ConfigurationHome $home
     *
     * @return $this
     */
    public function setHome(\App\Document\Configuration\ConfigurationHome $home)
    {
        $this->home = $home;

        return $this;
    }

    /**
     * Get home.
     *
     * @return App\Document\ConfigurationHome $home
     */
    public function getHome()
    {
        if (!$this->home) {
            $this->home = new ConfigurationHome();
        }

        return $this->home;
    }

    /**
     * Set logo.
     *
     * @param App\Document\ConfImage $logo
     *
     * @return $this
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo.
     *
     * @return App\Document\ConfImage $logo
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set logoInline.
     *
     * @param App\Document\ConfImage $logoInline
     *
     * @return $this
     */
    public function setLogoInline($logoInline)
    {
        $this->logoInline = $logoInline;

        return $this;
    }

    /**
     * Get logoInline.
     *
     * @return App\Document\ConfImage $logoInline
     */
    public function getLogoInline()
    {
        return $this->logoInline;
    }

    /**
     * Set socialShareImage.
     *
     * @param App\Document\ConfImage $socialShareImage
     *
     * @return $this
     */
    public function setSocialShareImage($socialShareImage)
    {
        $this->socialShareImage = $socialShareImage;

        return $this;
    }

    /**
     * Get socialShareImage.
     *
     * @return App\Document\ConfImage $socialShareImage
     */
    public function getSocialShareImage()
    {
        return $this->socialShareImage;
    }

    /**
     * Set favicon.
     *
     * @param App\Document\ConfImage $favicon
     *
     * @return $this
     */
    public function setFavicon($favicon)
    {
        $this->favicon = $favicon;

        return $this;
    }

    /**
     * Get favicon.
     *
     * @return App\Document\ConfImage $favicon
     */
    public function getFavicon()
    {
        return $this->favicon;
    }

    /**
     * Set activatePartnersPage.
     *
     * @param bool $activatePartnersPage
     *
     * @return $this
     */
    public function setActivatePartnersPage($activatePartnersPage)
    {
        $this->activatePartnersPage = $activatePartnersPage;

        return $this;
    }

    /**
     * Get activatePartnersPage.
     *
     * @return bool $activatePartnersPage
     */
    public function getActivatePartnersPage()
    {
        return $this->activatePartnersPage;
    }

    /**
     * Set partnerPageTitle.
     *
     * @param string $partnerPageTitle
     *
     * @return $this
     */
    public function setPartnerPageTitle($partnerPageTitle)
    {
        $this->partnerPageTitle = $partnerPageTitle;

        return $this;
    }

    /**
     * Get partnerPageTitle.
     *
     * @return string $partnerPageTitle
     */
    public function getPartnerPageTitle()
    {
        return $this->partnerPageTitle;
    }

    /**
     * Set activateAbouts.
     *
     * @param bool $activateAbouts
     *
     * @return $this
     */
    public function setActivateAbouts($activateAbouts)
    {
        $this->activateAbouts = $activateAbouts;

        return $this;
    }

    /**
     * Get activateAbouts.
     *
     * @return bool $activateAbouts
     */
    public function getActivateAbouts()
    {
        return $this->activateAbouts;
    }

    /**
     * Set aboutHeaderTitle.
     *
     * @param string $aboutHeaderTitle
     *
     * @return $this
     */
    public function setAboutHeaderTitle($aboutHeaderTitle)
    {
        $this->aboutHeaderTitle = $aboutHeaderTitle;

        return $this;
    }

    /**
     * Get aboutHeaderTitle.
     *
     * @return string $aboutHeaderTitle
     */
    public function getAboutHeaderTitle()
    {
        return $this->aboutHeaderTitle;
    }

    /**
     * Set user.
     *
     * @param App\Document\Configuration\ConfigurationUser $user
     *
     * @return $this
     */
    public function setUser(\App\Document\Configuration\ConfigurationUser $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return App\Document\Configuration\ConfigurationUser $user
     */
    public function getUser()
    {
        if (!$this->user) {
            $this->user = new ConfigurationUser();
        }

        return $this->user;
    }

    public function setDbName($dbName)
    {
        $this->dbName = $dbName;

        return $this;
    }

    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Set menu.
     *
     * @param App\Document\Configuration\ConfigurationMenu $menu
     *
     * @return $this
     */
    public function setMenu(\App\Document\Configuration\ConfigurationMenu $menu)
    {
        $this->menu = $menu;

        return $this;
    }

    /**
     * Get menu.
     *
     * @return App\Document\Configuration\ConfigurationMenu $menu
     */
    public function getMenu()
    {
        if (!$this->menu) {
            $this->menu = new ConfigurationMenu();
        }

        return $this->menu;
    }

    /**
     * Set infobar.
     *
     * @param App\Document\Configuration\ConfigurationInfobar $infobar
     *
     * @return $this
     */
    public function setInfobar(\App\Document\Configuration\ConfigurationInfobar $infobar)
    {
        $this->infobar = $infobar;

        return $this;
    }

    /**
     * Get infobar.
     *
     * @return App\Document\Configuration\ConfigurationInfobar $infobar
     */
    public function getInfobar()
    {
        if (!$this->infobar) {
            $this->infobar = new ConfigurationInfobar();
        }

        return $this->infobar;
    }

    /**
     * Set elementFormFieldsJson.
     *
     * @param string $elementFormFieldsJson
     *
     * @return $this
     */
    public function setElementFormFieldsJson($elementFormFieldsJson)
    {
        if ($elementFormFieldsJson)
            $this->elementFormFieldsJson = $elementFormFieldsJson;

        return $this;
    }

    /**
     * Get elementFormFieldsJson.
     *
     * @return string $elementFormFieldsJson
     */
    public function getElementFormFieldsJson()
    {
        return $this->elementFormFieldsJson;
    }

    /**
     * Set listModeFeature.
     *
     * @param App\Document\FeatureConfiguration $listModeFeature
     *
     * @return $this
     */
    public function setListModeFeature(\App\Document\FeatureConfiguration $listModeFeature)
    {
        $this->listModeFeature = $listModeFeature;

        return $this;
    }

    /**
     * Get listModeFeature.
     *
     * @return App\Document\FeatureConfiguration $listModeFeature
     */
    public function getListModeFeature()
    {
        if (!$this->listModeFeature) {
            $this->listModeFeature = new FeatureConfiguration();
        }

        return $this->listModeFeature;
    }

    /**
     * Set searchPlaceFeature.
     *
     * @param App\Document\FeatureConfiguration $searchPlaceFeature
     *
     * @return $this
     */
    public function setSearchPlaceFeature(\App\Document\FeatureConfiguration $searchPlaceFeature)
    {
        $this->searchPlaceFeature = $searchPlaceFeature;

        return $this;
    }

    /**
     * Get searchPlaceFeature.
     *
     * @return App\Document\FeatureConfiguration $searchPlaceFeature
     */
    public function getSearchPlaceFeature()
    {
        if (!$this->searchPlaceFeature) {
            $this->searchPlaceFeature = new FeatureConfiguration();
        }

        return $this->searchPlaceFeature;
    }

    /**
     * Set searchGeolocateFeature.
     *
     * @param App\Document\FeatureConfiguration $searchGeolocateFeature
     *
     * @return $this
     */
    public function setSearchGeolocateFeature(\App\Document\FeatureConfiguration $searchGeolocateFeature)
    {
        $this->searchGeolocateFeature = $searchGeolocateFeature;

        return $this;
    }

    /**
     * Get searchGeolocateFeature.
     *
     * @return App\Document\FeatureConfiguration $searchGeolocateFeature
     */
    public function getSearchGeolocateFeature()
    {
        if (!$this->searchGeolocateFeature) {
            $this->searchGeolocateFeature = new FeatureConfiguration();
        }

        return $this->searchGeolocateFeature;
    }

    /**
     * Set layersFeature.
     *
     * @param App\Document\FeatureConfiguration $layersFeature
     *
     * @return $this
     */
    public function setLayersFeature(\App\Document\FeatureConfiguration $layersFeature)
    {
        $this->layersFeature = $layersFeature;

        return $this;
    }

    /**
     * Get layersFeature.
     *
     * @return App\Document\FeatureConfiguration $layersFeature
     */
    public function getLayersFeature()
    {
        if (!$this->layersFeature) {
            $this->layersFeature = new FeatureConfiguration();
        }

        return $this->layersFeature;
    }

    /**
     * Set mapDefaultViewFeature.
     *
     * @param App\Document\FeatureConfiguration $mapDefaultViewFeature
     *
     * @return $this
     */
    public function setMapDefaultViewFeature(\App\Document\FeatureConfiguration $mapDefaultViewFeature)
    {
        $this->mapDefaultViewFeature = $mapDefaultViewFeature;

        return $this;
    }

    /**
     * Get mapDefaultViewFeature.
     *
     * @return App\Document\FeatureConfiguration $mapDefaultViewFeature
     */
    public function getMapDefaultViewFeature()
    {
        if (!$this->mapDefaultViewFeature) {
            $this->mapDefaultViewFeature = new FeatureConfiguration();
        }

        return $this->mapDefaultViewFeature;
    }

    /**
     * Set saveViewportInCookies.
     *
     * @param bool $saveViewportInCookies
     *
     * @return $this
     */
    public function setSaveViewportInCookies($saveViewportInCookies)
    {
        $this->saveViewportInCookies = $saveViewportInCookies;

        return $this;
    }

    /**
     * Get saveViewportInCookies.
     *
     * @return bool $saveViewportInCookies
     */
    public function getSaveViewportInCookies()
    {
        return $this->saveViewportInCookies;
    }

    /**
     * Set saveTileLayerInCookies.
     *
     * @param bool $saveTileLayerInCookies
     *
     * @return $this
     */
    public function setSaveTileLayerInCookies($saveTileLayerInCookies)
    {
        $this->saveTileLayerInCookies = $saveTileLayerInCookies;

        return $this;
    }

    /**
     * Get saveTileLayerInCookies.
     *
     * @return bool $saveTileLayerInCookies
     */
    public function getSaveTileLayerInCookies()
    {
        return $this->saveTileLayerInCookies;
    }

    /**
     * Set searchElementsFeature.
     *
     * @param App\Document\FeatureConfiguration $searchElementsFeature
     *
     * @return $this
     */
    public function setSearchElementsFeature(\App\Document\FeatureConfiguration $searchElementsFeature)
    {
        $this->searchElementsFeature = $searchElementsFeature;

        return $this;
    }

    /**
     * Get searchElementsFeature.
     *
     * @return App\Document\FeatureConfiguration $searchElementsFeature
     */
    public function getSearchElementsFeature()
    {
        if (!$this->searchElementsFeature) {
            $this->searchElementsFeature = new FeatureConfiguration();
        }

        return $this->searchElementsFeature;
    }
    /**
     * Set searchCategoriesFeature.
     *
     * @param App\Document\FeatureConfiguration $searchCategoriesFeature
     *
     * @return $this
     */
    public function setSearchCategoriesFeature(\App\Document\FeatureConfiguration $searchCategoriesFeature)
    {
        $this->searchCategoriesFeature = $searchCategoriesFeature;

        return $this;
    }

    /**
     * Get searchCategoriesFeature.
     *
     * @return App\Document\FeatureConfiguration $searchCategoriesFeature
     */
    public function getSearchCategoriesFeature()
    {
        if (!$this->searchCategoriesFeature) {
            $this->searchCategoriesFeature = new FeatureConfiguration();
        }

        return $this->searchCategoriesFeature;
    }

    /**
     * Set api.
     *
     * @param App\Document\Configuration\ConfigurationApi $api
     *
     * @return $this
     */
    public function setApi(\App\Document\Configuration\ConfigurationApi $api)
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Get api.
     *
     * @return App\Document\Configuration\ConfigurationApi $api
     */
    public function getApi()
    {
        if (!$this->api) {
            $this->api = new ConfigurationApi();
        }

        return $this->api;
    }

    /**
     * Set osm.
     *
     * @param App\Document\Configuration\ConfigurationOsm $osm
     *
     * @return $this
     */
    public function setOsm(\App\Document\Configuration\ConfigurationOsm $osm)
    {
        $this->osm = $osm;

        return $this;
    }

    /**
     * Get osm.
     *
     * @return App\Document\Configuration\ConfigurationOsm $osm
     */
    public function getOsm()
    {
        if (!$this->osm) {
            $this->osm = new ConfigurationOsm();
        }

        return $this->osm;
    }

    public function setSaas($saas)
    {
        $this->saas = $saas;

        return $this;
    }

    public function getSaas()
    {
        if (!$this->saas) {
            $this->saas = new ConfigurationSaas();
        }

        return $this->saas;
    }

    /**
     * Set theme.
     *
     * @param string $theme
     *
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme.
     *
     * @return string $theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set textColor.
     *
     * @param string $textColor
     *
     * @return $this
     */
    public function setTextColor($textColor)
    {
        $this->textColor = $textColor;

        return $this;
    }

    /**
     * Get textColor.
     *
     * @return string $textColor
     */
    public function getTextColor()
    {
        return $this->textColor;
    }

    /**
     * Get secondaryColor.
     *
     * @return string $secondaryColor
     */
    public function getSecondaryColor()
    {
        return $this->secondaryColor;
    }

    /**
     * Set backgroundColor.
     *
     * @param string $backgroundColor
     *
     * @return $this
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * Get backgroundColor.
     *
     * @return string $backgroundColor
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Set homeBackgroundColor.
     *
     * @param string $homeBackgroundColor
     *
     * @return $this
     */
    public function setHomeBackgroundColor($homeBackgroundColor)
    {
        $this->homeBackgroundColor = $homeBackgroundColor;

        return $this;
    }

    /**
     * Get homeBackgroundColor.
     *
     * @return string $homeBackgroundColor
     */
    public function getHomeBackgroundColor()
    {
        return $this->homeBackgroundColor;
    }

    /**
     * Set textDarkColor.
     *
     * @param string $textDarkColor
     *
     * @return $this
     */
    public function setTextDarkColor($textDarkColor)
    {
        $this->textDarkColor = $textDarkColor;

        return $this;
    }

    /**
     * Get textDarkColor.
     *
     * @return string $textDarkColor
     */
    public function getTextDarkColor()
    {
        return $this->textDarkColor;
    }

    /**
     * Set textDarkSoftColor.
     *
     * @param string $textDarkSoftColor
     *
     * @return $this
     */
    public function setTextDarkSoftColor($textDarkSoftColor)
    {
        $this->textDarkSoftColor = $textDarkSoftColor;

        return $this;
    }

    /**
     * Get textDarkSoftColor.
     *
     * @return string $textDarkSoftColor
     */
    public function getTextDarkSoftColor()
    {
        return $this->textDarkSoftColor;
    }

    /**
     * Set textLightColor.
     *
     * @param string $textLightColor
     *
     * @return $this
     */
    public function setTextLightColor($textLightColor)
    {
        $this->textLightColor = $textLightColor;

        return $this;
    }

    /**
     * Get textLightColor.
     *
     * @return string $textLightColor
     */
    public function getTextLightColor()
    {
        return $this->textLightColor;
    }

    /**
     * Set textLightSoftColor.
     *
     * @param string $textLightSoftColor
     *
     * @return $this
     */
    public function setTextLightSoftColor($textLightSoftColor)
    {
        $this->textLightSoftColor = $textLightSoftColor;

        return $this;
    }

    /**
     * Get textLightSoftColor.
     *
     * @return string $textLightSoftColor
     */
    public function getTextLightSoftColor()
    {
        return $this->textLightSoftColor;
    }

    /**
     * Set contentBackgroundColor.
     *
     * @param string $contentBackgroundColor
     *
     * @return $this
     */
    public function setContentBackgroundColor($contentBackgroundColor)
    {
        $this->contentBackgroundColor = $contentBackgroundColor;

        return $this;
    }

    /**
     * Get contentBackgroundColor.
     *
     * @return string $contentBackgroundColor
     */
    public function getContentBackgroundColor()
    {
        return $this->contentBackgroundColor;
    }

    /**
     * Set headerColor.
     *
     * @param string $headerColor
     *
     * @return $this
     */
    public function setHeaderColor($headerColor)
    {
        $this->headerColor = $headerColor;

        return $this;
    }

    /**
     * Get headerColor.
     *
     * @return string $headerColor
     */
    public function getHeaderColor()
    {
        return $this->headerColor;
    }

    /**
     * Set headerTextColor.
     *
     * @param string $headerTextColor
     *
     * @return $this
     */
    public function setHeaderTextColor($headerTextColor)
    {
        $this->headerTextColor = $headerTextColor;

        return $this;
    }

    /**
     * Get headerTextColor.
     *
     * @return string $headerTextColor
     */
    public function getHeaderTextColor()
    {
        return $this->headerTextColor;
    }

    /**
     * Set headerHoverColor.
     *
     * @param string $headerHoverColor
     *
     * @return $this
     */
    public function setHeaderHoverColor($headerHoverColor)
    {
        $this->headerHoverColor = $headerHoverColor;

        return $this;
    }

    /**
     * Get headerHoverColor.
     *
     * @return string $headerHoverColor
     */
    public function getHeaderHoverColor()
    {
        return $this->headerHoverColor;
    }

    /**
     * Set searchBarColor.
     *
     * @param string $searchBarColor
     *
     * @return $this
     */
    public function setSearchBarColor($searchBarColor)
    {
        $this->searchBarColor = $searchBarColor;

        return $this;
    }

    /**
     * Get searchBarColor.
     *
     * @return string $searchBarColor
     */
    public function getSearchBarColor()
    {
        return $this->searchBarColor;
    }

    /**
     * Set disableColor.
     *
     * @param string $disableColor
     *
     * @return $this
     */
    public function setDisableColor($disableColor)
    {
        $this->disableColor = $disableColor;

        return $this;
    }

    /**
     * Get disableColor.
     *
     * @return string $disableColor
     */
    public function getDisableColor()
    {
        return $this->disableColor;
    }

    /**
     * Set errorColor.
     *
     * @param string $errorColor
     *
     * @return $this
     */
    public function setErrorColor($errorColor)
    {
        $this->errorColor = $errorColor;

        return $this;
    }

    /**
     * Get errorColor.
     *
     * @return string $errorColor
     */
    public function getErrorColor()
    {
        return $this->errorColor;
    }

    /**
     * Set pendingColor.
     *
     * @param string $pendingColor
     *
     * @return $this
     */
    public function setPendingColor($pendingColor)
    {
        $this->pendingColor = $pendingColor;

        return $this;
    }

    /**
     * Get pendingColor.
     *
     * @return string $pendingColor
     */
    public function getPendingColor()
    {
        return $this->pendingColor;
    }

    /**
     * Set interactiveSectionColor.
     *
     * @param string $interactiveSectionColor
     *
     * @return $this
     */
    public function setInteractiveSectionColor($interactiveSectionColor)
    {
        $this->interactiveSectionColor = $interactiveSectionColor;

        return $this;
    }

    /**
     * Get interactiveSectionColor.
     *
     * @return string $interactiveSectionColor
     */
    public function getInteractiveSectionColor()
    {
        return $this->interactiveSectionColor;
    }

    /**
     * Set contentBackgroundElementBodyColor.
     *
     * @param string $contentBackgroundElementBodyColor
     *
     * @return $this
     */
    public function setContentBackgroundElementBodyColor($contentBackgroundElementBodyColor)
    {
        $this->contentBackgroundElementBodyColor = $contentBackgroundElementBodyColor;

        return $this;
    }

    /**
     * Get contentBackgroundElementBodyColor.
     *
     * @return string $contentBackgroundElementBodyColor
     */
    public function getContentBackgroundElementBodyColor()
    {
        return $this->contentBackgroundElementBodyColor;
    }

    /**
     * Set marker.
     *
     * @param App\Document\Configuration\ConfigurationMarker $marker
     *
     * @return $this
     */
    public function setMarker(\App\Document\Configuration\ConfigurationMarker $marker)
    {
        $this->marker = $marker;

        return $this;
    }

    /**
     * Get marker.
     *
     * @return App\Document\Configuration\ConfigurationMarker $marker
     */
    public function getMarker()
    {
        if (!$this->marker) {
            $this->marker = new ConfigurationMarker();
        }

        return $this->marker;
    }

    /**
     * Set dataLicenseUrl.
     *
     * @param string $dataLicenseUrl
     *
     * @return $this
     */
    public function setDataLicenseUrl($dataLicenseUrl)
    {
        $this->dataLicenseUrl = $dataLicenseUrl;

        return $this;
    }

    /**
     * Get dataLicenseUrl.
     *
     * @return string $dataLicenseUrl
     */
    public function getDataLicenseUrl()
    {
        return $this->dataLicenseUrl;
    }

    /**
     * Set searchExcludingWords.
     *
     * @param string $searchExcludingWords
     *
     * @return $this
     */
    public function setSearchExcludingWords($searchExcludingWords)
    {
        $this->searchExcludingWords = $searchExcludingWords;

        return $this;
    }

    /**
     * Get searchExcludingWords.
     *
     * @return string $searchExcludingWords
     */
    public function getSearchExcludingWords()
    {
        return str_replace(' ', '', $this->searchExcludingWords);
    }

    /**
     * Set appNameShort.
     *
     * @param string $appNameShort
     *
     * @return $this
     */
    public function setAppNameShort($appNameShort)
    {
        $this->appNameShort = $appNameShort;

        return $this;
    }

    /**
     * Get appNameShort.
     *
     * @return string $appNameShort
     */
    public function getAppNameShort()
    {
        return $this->appNameShort;
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @param string $packageName
     * @return $this
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
        return $this;
    }

    /**
     * @return string
     */
    public function getSha256CertFingerprints()
    {
        return $this->sha256CertFingerprints;
    }

    /**
     * @param string $sha256CertFingerprints
     * @return $this
     */
    public function setSha256CertFingerprints($sha256CertFingerprints)
    {
        $this->sha256CertFingerprints = $sha256CertFingerprints;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHideHeaderInPwa()
    {
        return $this->hideHeaderInPwa;
    }

    /**
     * @param bool $hideHeaderInPwa
     * @return $this
     */
    public function setHideHeaderInPwa($hideHeaderInPwa)
    {
        $this->hideHeaderInPwa = $hideHeaderInPwa;
        return $this;
    }

    public function getPublishOnSaasPage() {
        return $this->publishOnSaasPage;
    }
    public function setPublishOnSaasPage($bool) {
        $this->publishOnSaasPage = $bool;
        return $this;
    }

    public function getCustomDomain()
    {
        return $this->customDomain;
    }
    public function setCustomDomain($domain)
    {
        $this->customDomain = $domain;
        return $this;
    }

    /**
     * Get the value of duplicates
     */ 
    public function getDuplicates()
    {
        if (!$this->duplicates) {
            $this->duplicates = new ConfigurationDuplicates();
        }
        return $this->duplicates;
    }

    /**
     * Set the value of duplicates
     *
     * @return  self
     */ 
    public function setDuplicates($duplicates)
    {
        $this->duplicates = $duplicates;

        return $this;
    }

    /**
     * Get the value of locale
     */ 
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the value of locale
     *
     * @return  self
     */ 
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}
