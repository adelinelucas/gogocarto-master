<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Keep a trace of the migrations already ran. See MigrationCommand.
 *
 * @MongoDB\Document
 */
class MigrationState
{
    /** @MongoDB\Id */
    private $id;

    /**
     * @MongoDB\Field(type="int")
     */
    private $migrationIndex = 0;

    /**
     * @MongoDB\Field(type="int")
     */
    private $commandsIndex = 0;

    /**
     * @MongoDB\Field(type="int")
     */
    private $messagesIndex = 0;

    /**
     * Get id.
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set migrationIndex.
     *
     * @param int $migrationIndex
     *
     * @return $this
     */
    public function setMigrationIndex($migrationIndex)
    {
        $this->migrationIndex = $migrationIndex;

        return $this;
    }

    /**
     * Get migrationIndex.
     *
     * @return int $migrationIndex
     */
    public function getMigrationIndex()
    {
        return $this->migrationIndex;
    }

    /**
     * Set messagesIndex.
     *
     * @param int $messagesIndex
     *
     * @return $this
     */
    public function setMessagesIndex($messagesIndex)
    {
        $this->messagesIndex = $messagesIndex;

        return $this;
    }

    /**
     * Get messagesIndex.
     *
     * @return int $messagesIndex
     */
    public function getMessagesIndex()
    {
        return $this->messagesIndex;
    }

    /**
     * Set commandsIndex.
     *
     * @param int $commandsIndex
     *
     * @return $this
     */
    public function setCommandsIndex($commandsIndex)
    {
        $this->commandsIndex = $commandsIndex;

        return $this;
    }

    /**
     * Get commandsIndex.
     *
     * @return int $commandsIndex
     */
    public function getCommandsIndex()
    {
        if (!$this->commandsIndex) {
            $this->commandsIndex = 0;
        }

        return $this->commandsIndex;
    }
}
