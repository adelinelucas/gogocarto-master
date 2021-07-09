<?php

namespace App\Services;

use Doctrine\ODM\MongoDB\DocumentManager;

class DocumentManagerFactory
{
    /**
     * DocumentManager created by Symfony.
     *
     * @var DocumentManager
     */
    private $currentDocumentManager;

    /* name of the root database */
    private $rootDB;
    /**
     * All DocumentManagers created by Factory so far.
     *
     * @var DocumentManager[]
     */
    private $instances = array();

    public function __construct(DocumentManager $dm, $rootDB)
    {
        $this->currentDocumentManager = $dm;
        $this->rootDB = $rootDB;
    }

    // Create a new Manager associated with the databaseName param
    // This does not change the current document manager
    // It's very usefull when we want for example to do something on each DB
    public function createForDB(String $databaseName)
    {
        if (isset($this->instances[$databaseName])) {
            return $this->instances[$databaseName];
        }
        $configuration = clone $this->currentDocumentManager->getConfiguration();
        $newDm = DocumentManager::create(
            $this->currentDocumentManager->getConnection(),
            $configuration,
            $this->currentDocumentManager->getEventManager()
        );
        $newDm->getConfiguration()->setDefaultDB($databaseName);
        return $this->instances[$databaseName] = $newDm;
    }

    // In SAAS Mode, it get the document manager of the root project
    public function getRootManager()
    {
        return $this->createForDB($this->rootDB);
    }

    public function getCurrentManager()
    {
        return $this->currentDocumentManager;
    }

    public function getCurrentDbName()
    {
        return $this->getCurrentManager()->getConfiguration()->getDefaultDB();
    }

    public function isRootProject()
    {
        return $this->getCurrentDbName() == $this->rootDB;
    }

    // Replace the current manager to use a specific DB.
    // Useful when using symfony Command because in this case the
    // document manager is not automatically associated with the correct database name
    // cause there is no url to get the subdomain
    public function switchCurrManagerToUseDb($databaseName)
    {
        $this->currentDocumentManager->getConfiguration()->setDefaultDB($databaseName);
        return $this->currentDocumentManager;
    }
}