<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class GoGoLogLevel
{
    const Debug = 'debug';
    const Info = 'info';
    const Warning = 'warning';
    const Error = 'error';
    const Success = 'success';
}

/**
 * @MongoDB\Document
 *
 * @MongoDB\InheritanceType("SINGLE_COLLECTION")
 * @MongoDB\DiscriminatorField("type")
 * @MongoDB\DiscriminatorMap({"standard"="GoGoLog", "import"="GoGoLogImport", "update"="GoGoLogUpdate"})
 * @MongoDB\DefaultDiscriminatorValue("standard")
 */
class GoGoLog
{
    /**
     * @var int
     *
     * @MongoDB\Id(strategy="INCREMENT")
     */
    private $id;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @MongoDB\Index
     */
    private $level;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    private $message;

    /**
     * Other informations stored into the log.
     *
     * @MongoDB\Field(type="hash")
     */
    private $data = [];

    /**
     * @var bool
     * When user click "dismiss" log, it will not appear again
     * @MongoDB\Field(type="bool") @MongoDB\Index
     */
    private $hidden = false;

    /**
     * @var date
     *
     * @MongoDB\Field(type="date")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    public function __construct($level = null, $message = null, $data = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->data = $data;
    }

    public function displayTimestamp()
    {
        return true;
    }

    public function displayMessage(TranslatorInterface $t = null)
    {
        return $this->getMessage();
    }

    public function getDataProp($key)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
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
     * Set message.
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string $message
     */
    public function getMessage()
    {
        return $this->message;
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
     * Set hidden.
     *
     * @param bool $hidden
     *
     * @return $this
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden.
     *
     * @return bool $hidden
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set level.
     *
     * @param string $level
     *
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level.
     *
     * @return string $level
     */
    public function getLevel()
    {
        return $this->level;
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
}
