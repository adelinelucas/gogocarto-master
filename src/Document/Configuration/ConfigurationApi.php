<?php

namespace App\Document\Configuration;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/** @MongoDB\EmbeddedDocument */
class ConfigurationApi
{
    /**
     * List of the custom properties we don't want to share in the public API.
     *
     * @MongoDB\Field(type="collection")
     */
    public $publicApiPrivateProperties = [];

    /**
     * Set publicApiPrivateProperties.
     *
     * @param collection $publicApiPrivateProperties
     *
     * @return $this
     */
    public function setPublicApiPrivateProperties($publicApiPrivateProperties)
    {
        $this->publicApiPrivateProperties = $publicApiPrivateProperties;

        return $this;
    }

    /**
     * Get publicApiPrivateProperties.
     *
     * @return collection $publicApiPrivateProperties
     */
    public function getPublicApiPrivateProperties()
    {
        return $this->publicApiPrivateProperties;
    }
}
