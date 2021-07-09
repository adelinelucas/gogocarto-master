<?php

namespace App\DataFixtures\MongoDB;

use App\Command\MigrationCommand;
use App\Document\MigrationState;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadMigrationState implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $migrationState = new MigrationState();
        $migrationState->setMigrationIndex(count(MigrationCommand::$migrations));
        $migrationState->setCommandsIndex(count(MigrationCommand::$commands));
        $migrationState->setMessagesIndex(count(MigrationCommand::$messages));
        $manager->persist($migrationState);
        $manager->flush();
    }
}
