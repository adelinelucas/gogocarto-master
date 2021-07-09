<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

abstract class PostStatus
{
    const Dispatched = 'dispatched';
    const Failed = 'failed';
}

/** @MongoDB\EmbeddedDocument */
class WebhookPost
{
    /** 
     * If webhook is null, it mean it a special WebhookPost which mean custom handling
     * (for example for OSM). See WekbookService
     * @MongoDB\Index
     * @MongoDB\ReferenceOne(targetDocument="App\Document\Webhook", cascade={"persist"}) 
     * */
    public $webhook;

    /** @MongoDB\Field(type="int") @MongoDB\Index */
    public $numAttempts;

    /** @MongoDB\Field(type="date") @MongoDB\Index */
    public $nextAttemptAt;

    // non persisted attributes
    private $data;
    private $url;

    public function __construct()
    {
        $this->numAttempts = 0;
    }

    public function __toString()
    {
        return (string) $this->getUrl();
    }

    /**
     * Set num attempts.
     *
     * @param int $numAttempts
     *
     * @return $this
     */
    public function setNumAttempts($numAttempts)
    {
        $this->numAttempts = $numAttempts;

        return $this;
    }

    /**
     * Increment num attempts.
     *
     * @param int $numAttempts
     *
     * @return $this
     */
    public function incrementNumAttempts()
    {
        ++$this->numAttempts;

        return $this->numAttempts;
    }

    /**
     * Get num attempts.
     *
     * @return int $numAttempts
     */
    public function getNumAttempts()
    {
        return $this->numAttempts;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
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
     * @return \DateTime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set webhook.
     *
     * @param App\Document\Webhook $webhook
     *
     * @return $this
     */
    public function setWebhook(\App\Document\Webhook $webhook)
    {
        $this->webhook = $webhook;

        return $this;
    }

    /**
     * Get webhook.
     *
     * @return App\Document\Webhook $webhook
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * Set dispatched.
     *
     * @param bool $dispatched
     *
     * @return $this
     */
    public function setDispatched($dispatched)
    {
        $this->dispatched = $dispatched;

        return $this;
    }

    /**
     * Get dispatched.
     *
     * @return bool $dispatched
     */
    public function getDispatched()
    {
        return $this->dispatched;
    }

    public function isDispatched()
    {
        return $this->getDispatched();
    }

    /**
     * Set nextAttemptAt.
     *
     * @param date $nextAttemptAt
     *
     * @return $this
     */
    public function setNextAttemptAt($nextAttemptAt)
    {
        $this->nextAttemptAt = $nextAttemptAt;

        return $this;
    }

    /**
     * Get nextAttemptAt.
     *
     * @return date $nextAttemptAt
     */
    public function getNextAttemptAt()
    {
        return $this->nextAttemptAt;
    }

    /**
     * Set data.
     *
     * @param string $data
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
     * @return string $data
     */
    public function getData()
    {
        return $this->data;
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
}
