<?php

namespace App\Document\Configuration;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/** @MongoDB\EmbeddedDocument */
class ConfigurationInfobar
{
    /** @MongoDB\Field(type="int") */
    public $width = null;

    /** @MongoDB\Field(type="string") */
    public $headerTemplate = null;

    /** @MongoDB\Field(type="bool") */
    public $headerTemplateUseMarkDown = true;

    /** @MongoDB\Field(type="string") */
    public $bodyTemplate = null;

    /** @MongoDB\Field(type="bool") */
    public $bodyTemplateUseMarkDown = true;

    /**
     * Set width.
     *
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width.
     *
     * @return int $width
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set bodyTemplate.
     *
     * @param string $bodyTemplate
     *
     * @return $this
     */
    public function setBodyTemplate($bodyTemplate)
    {
        $this->bodyTemplate = $bodyTemplate;

        return $this;
    }

    /**
     * Get bodyTemplate.
     *
     * @return string $bodyTemplate
     */
    public function getBodyTemplate()
    {
        return $this->bodyTemplate;
    }

    /**
     * Set headerTemplate.
     *
     * @param string $headerTemplate
     *
     * @return $this
     */
    public function setHeaderTemplate($headerTemplate)
    {
        $this->headerTemplate = $headerTemplate;

        return $this;
    }

    /**
     * Get headerTemplate.
     *
     * @return string $headerTemplate
     */
    public function getHeaderTemplate()
    {
        return $this->headerTemplate;
    }

    /**
     * Set headerTemplateUseMarkDown.
     *
     * @param bool $headerTemplateUseMarkDown
     *
     * @return $this
     */
    public function setHeaderTemplateUseMarkDown($headerTemplateUseMarkDown)
    {
        $this->headerTemplateUseMarkDown = $headerTemplateUseMarkDown;

        return $this;
    }

    /**
     * Get headerTemplateUseMarkDown.
     *
     * @return bool $headerTemplateUseMarkDown
     */
    public function getHeaderTemplateUseMarkDown()
    {
        return $this->headerTemplateUseMarkDown;
    }

    /**
     * Set bodyTemplateUseMarkDown.
     *
     * @param bool $bodyTemplateUseMarkDown
     *
     * @return $this
     */
    public function setBodyTemplateUseMarkDown($bodyTemplateUseMarkDown)
    {
        $this->bodyTemplateUseMarkDown = $bodyTemplateUseMarkDown;

        return $this;
    }

    /**
     * Get bodyTemplateUseMarkDown.
     *
     * @return bool $bodyTemplateUseMarkDown
     */
    public function getBodyTemplateUseMarkDown()
    {
        return $this->bodyTemplateUseMarkDown;
    }
}
