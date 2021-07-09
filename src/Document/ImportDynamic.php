<?php

namespace App\Document;

use Datetime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * External source to load dynamically.
 *
 * @MongoDB\Document
 */
class ImportDynamic extends Import
{
    /**
     * Get Data from OpenStreetMap using overpass query
     * @MongoDB\Field(type="string")
     */
    public $osmQueriesJson;
    
    /**
     * @var string
     * @MongoDB\Field(type="int")
     */
    private $refreshFrequencyInDays;

    /**
     * @var date
     *
     * @MongoDB\Field(type="date")
     */
    private $nextRefresh = null;

    /**
     * Users to be tonified when error during import, or where new ontology/taxonomy mapping
     * @MongoDB\ReferenceMany(targetDocument="App\Document\User")
     */
    private $usersToNotify;

    /**
     * TODO : allow synchronizing OSM import if an OSM account is configured
     * @MongoDB\Field(type="bool")
     */
    private $isSynchronized = false;

    public function isDynamicImport()
    {
        return true;
    }

    public function updateNextRefreshDate()
    {
        if (0 == $this->getRefreshFrequencyInDays()) {
            $this->setNextRefresh(null);
        } else {
            $interval = new \DateInterval('P'.$this->getRefreshFrequencyInDays().'D');
            $date = new DateTime();
            $date->setTimestamp(time());
            $this->setNextRefresh($date->add($interval));
        }
    }

    /**
     * Set refreshFrequencyInDays.
     *
     * @param int $refreshFrequencyInDays
     *
     * @return $this
     */
    public function setRefreshFrequencyInDays($refreshFrequencyInDays)
    {
        $this->refreshFrequencyInDays = $refreshFrequencyInDays;
        $this->updateNextRefreshDate();

        return $this;
    }

    /**
     * Get refreshFrequencyInDays.
     *
     * @return int $refreshFrequencyInDays
     */
    public function getRefreshFrequencyInDays()
    {
        return $this->refreshFrequencyInDays;
    }

    /**
     * Set nextRefresh.
     *
     * @param date $nextRefresh
     *
     * @return $this
     */
    public function setNextRefresh($nextRefresh)
    {
        $this->nextRefresh = $nextRefresh;

        return $this;
    }

    /**
     * Get nextRefresh.
     *
     * @return date $nextRefresh
     */
    public function getNextRefresh()
    {
        return $this->nextRefresh;
    }

    public function getOsmQueriesJson()
    {
        return $this->osmQueriesJson ?? '{"queries": [], "address": "", "bounds": null}';
    }

    public function getOsmQueries()
    {
        return json_decode($this->getOsmQueriesJson())->queries;
    }

    public function setOsmQueriesJson($json) 
    {
        $this->osmQueriesJson = $json;
        return $this;
    }

    /**
     * Get users to be tonified when error during import, or where new ontology/taxonomy mapping
     */ 
    public function getUsersToNotify()
    {
        return $this->usersToNotify;
    }

    /**
     * Set users to be tonified when error during import, or where new ontology/taxonomy mapping
     *
     * @return  self
     */ 
    public function setUsersToNotify($usersToNotify)
    {
        $this->usersToNotify = $usersToNotify;

        return $this;
    }

    /**
     * Get the value of isSynchronized
     */ 
    public function getIsSynchronized()
    {
        return $this->isSynchronized;
    }

    /**
     * Set the value of isSynchronized
     *
     * @return  self
     */ 
    public function setIsSynchronized($isSynchronized)
    {
        $this->isSynchronized = $isSynchronized;

        return $this;
    }
}
