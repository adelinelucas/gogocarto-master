<?php

namespace App\Document;

use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @MongoDB\Document
 * @Unique(fields="name")
 * @Unique(fields="domainName")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"name"="text"})
 * })
 */
class Project
{
    /**
     * @var int
     *
     * @MongoDB\Id(strategy="INCREMENT")
     */
    private $id;

    /**
     * @MongoDB\Field(type="string") @MongoDB\Index
     */
    public $name;

    /**
     * domain-name.my-server-url.org.
     *
     * @MongoDB\Field(type="string") @MongoDB\Index
     */
    public $domainName;

    // not persisted
    public $homeUrl;

    /** @MongoDB\Field(type="string") */
    public $imageUrl;

    /** @MongoDB\Field(type="string") */
    private $description;

    /** @MongoDB\Field(type="int") @MongoDB\Index */
    public $dataSize = 0;

    /** @MongoDB\Field(type="string") */
    private $adminEmails;

    /** @MongoDB\Field(type="date") @MongoDB\Index */
    private $lastLogin;

    /** @MongoDB\Field(type="date") @MongoDB\Index */
    private $warningToDeleteProjectSentAt;

    /** @MongoDB\Field(type="string") */
    public $tags;

    /**
    * Pin some project to make them more visible on home page
    * @MongoDB\Field(type="bool") @MongoDB\Index
    */
    private $pinned = false;

    /**
     * @var date
     *
     * @MongoDB\Field(type="date") @MongoDB\Index
     * @Gedmo\Timestampable(on="create")
     */
    public $createdAt;

    /**
    * Each project can choose to be published or not on the home project list
    *
    * @MongoDB\Field(type="bool") @MongoDB\Index
    */
    private $published;

    /**
    * @MongoDB\Field(type="date")
    */
    public $publishedAt;

    /**
     * Each project is updated on a regular basis, i.e. every day for example
     * @MongoDB\Field(type="date") @MongoDB\Index
     */
    private $nextUpdateAt;

    /**
    * @MongoDB\Field(type="bool") @MongoDB\Index
    */
    private $haveWebhooks;

    /**
    * @MongoDB\Field(type="bool") @MongoDB\Index
    */
    private $haveNewsletter;

    public function getDbName()
    {
        return $this->getDomainName();
    }

    public function __toString()
    {
        return $this->getName() ? $this->getName() : '';
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
     * Set domainName.
     *
     * @param string $domainName
     *
     * @return $this
     */
    public function setDomainName($domainName)
    {
        $domainName = str_replace('.', '', $domainName); // remove dot, not allowed
        $domainName = str_replace('gogocartofr', '', $domainName); // if people enter test.gogocarto.fr instead of just test
        $domainName = substr($domainName, 0, 30); // max 30 characters
        $this->domainName = $domainName;

        return $this;
    }

    /**
     * Get domainName.
     *
     * @return string $domainName
     */
    public function getDomainName()
    {
        return $this->domainName;
    }

    /**
     * Set homeUrl.
     *
     * @param string $homeUrl
     *
     * @return $this
     */
    public function setHomeUrl($homeUrl)
    {
        $this->homeUrl = $homeUrl;

        return $this;
    }

    /**
     * Get homeUrl.
     *
     * @return string $homeUrl
     */
    public function getHomeUrl()
    {
        return $this->homeUrl;
    }

    /**
     * Set imageUrl.
     *
     * @param string $imageUrl
     *
     * @return $this
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl.
     *
     * @return string $imageUrl
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set dataSize.
     *
     * @param int $dataSize
     *
     * @return $this
     */
    public function setDataSize($dataSize)
    {
        $this->dataSize = $dataSize;

        return $this;
    }

    /**
     * Get dataSize.
     *
     * @return int $dataSize
     */
    public function getDataSize()
    {
        return $this->dataSize;
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

    public function getAdminEmails() {
        return $this->adminEmails;
    }
    public function getAdminEmailsArray() {
        return explode(',', $this->adminEmails);
    }
    public function setAdminEmails($emails) {
        if (!$this->adminEmails) $emails = "";
        $this->adminEmails = $emails;
        return $this;
    }
    public function getPublished() {
        return $this->published;
    }
    public function setPublished($bool) {
        $this->published = $bool;
        if ($bool && !$this->publishedAt) {
            $this->setPublishedAt(time());
        }
    }
    public function getPublishedAt() {
        return $this->publishedAt;
    }
    public function setPublishedAt($date) {
        $this->publishedAt = $date;
        return $this;
    }
    public function getPinned() {
        return $this->pinned;
    }
    public function setPinned($bool) {
        $this->pinned = $bool;
        return $this;
    }
    public function getTags() {
        return $this->tags;
    }
    public function setTags($tags) {
        $this->tags = $tags;
        return $this;
    }
    public function getLastLogin() {
        return $this->lastLogin;
    }
    public function setLastLogin($date) {
        // That's mean that a user logs in again, so we cancel the deletion
        if ($this->lastLogin != $date) {
            $this->warningToDeleteProjectSentAt = null;
        }
        $this->lastLogin = $date;
        return $this;
    }
    public function getWarningToDeleteProjectSentAt() {
        return $this->warningToDeleteProjectSentAt;
    }
    public function setWarningToDeleteProjectSentAt($date) {
        $this->warningToDeleteProjectSentAt = $date;
        return $this;
    }

    /**
     * Get each project is updated on a regular basis, i.e. every day for example
     */ 
    public function getNextUpdateAt()
    {
        return $this->nextUpdateAt;
    }

    /**
     * Set each project is updated on a regular basis, i.e. every day for example
     *
     * @return  self
     */ 
    public function setNextUpdateAt($nextUpdateAt)
    {
        $this->nextUpdateAt = $nextUpdateAt;

        return $this;
    }

    /**
     * Get the value of haveWebhooks
     */ 
    public function getHaveWebhooks()
    {
        return $this->haveWebhooks;
    }

    /**
     * Set the value of haveWebhooks
     *
     * @return  self
     */ 
    public function setHaveWebhooks($haveWebhooks)
    {
        $this->haveWebhooks = $haveWebhooks;

        return $this;
    }

    /**
     * Get the value of haveNewsletter
     */ 
    public function getHaveNewsletter()
    {
        return $this->haveNewsletter;
    }

    /**
     * Set the value of haveNewsletter
     *
     * @return  self
     */ 
    public function setHaveNewsletter($haveNewsletter)
    {
        $this->haveNewsletter = $haveNewsletter;

        return $this;
    }
}
