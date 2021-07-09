<?php

namespace App\Command;

use App\Document\MigrationState;
use App\Services\AsyncService;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Services\DocumentManagerFactory;
use Symfony\Component\Console\Command\Command;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command to update database when schema need migration
 * Also provide some update message in the admin dashboard.
 */
class MigrationCommand extends Command
{
    // -----------------------------------------------------------------
    // DO NOT REMOVE A SINGLE ELEMENT OF THOSE ARRAYS, ONLY ADD NEW ONES
    // -----------------------------------------------------------------
    public static $migrations = [
      // v2.4.6
      'db.TileLayer.updateMany({name:"cartodb"}, {$set: {attribution:"&copy; <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a> &copy; <a href=\"http://cartodb.com/attributions\">CartoDB</a>"}})',
      'db.TileLayer.updateMany({name:"hydda"}, {$set: {attribution:"Tiles courtesy of <a href=\"http://openstreetmap.se/\" target=\"_blank\">OpenStreetMap Sweden</a> &mdash; Map data &copy; <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>"}})',
      'db.TileLayer.updateMany({name:"wikimedia"}, {$set: {attribution:"<a href=\"https://wikimediafoundation.org/wiki/Maps_Terms_of_Use\">Wikimedia</a> | Map data © <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap contributors</a>"}})',
      'db.TileLayer.updateMany({name:"lyrk"}, {$set: {attribution:"&copy Lyrk | Map data &copy; <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>"}})',
      'db.TileLayer.updateMany({name:"osmfr"}, {$set: {attribution:"&copy; Openstreetmap France | &copy; <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>"}})',
      'db.TileLayer.updateMany({name:"stamenWaterColor"}, {$set: {attribution:"Map tiles by <a href=\"http://stamen.com\">Stamen Design</a>, <a href=\"http://creativecommons.org/licenses/by/3.0\">CC BY 3.0</a> &mdash; Map data &copy; <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>"}})',
      // v3.1.0
      "db.Element.dropIndex(\"name_text\");",
      "db.Element.dropIndex(\"search_index\");",
      "db.Element.createIndex( {name: \"text\"}, { name: \"search_index\", default_language: \"french\", weights: {name: 1} });",
      // v3.2
      'db.Configuration.updateMany({}, {$set: {"user.loginWithLesCommuns": true, "user.loginWithLesGoogle": true, "user.loginWithFacebook": true}});',
      'db.Option.updateMany({}, {$set: {osmTags: {}}})',
      'var mapping = {}; 
       db.Element.find({ privateData: { $exists: true, $ne: {} } }).forEach(function(doc){Object.keys(doc.privateData).forEach(function(key){mapping["privateData." + key]="data." + key})}); 
       db.Element.updateMany({ privateData: { $exists: true, $ne: {} } }, {$rename: mapping})',
       // v3.2.8
       'db.Configuration.find().forEach(function(conf) { conf.elementFormFieldsJson = conf.elementFormFieldsJson.replace(/&lt;/g,"<").replace(/&gt;/g,">"); db.Configuration.save(conf); })'
    ];

    public static $commands = [
      // v2.3.1
      'app:elements:updateJson all',
      // v2.3.4
      'app:elements:updateJson all',
      // v2.4.5
      'app:elements:updateJson all',
      // v3.2.0
      'app:elements:updateJson all',
      // v3.2.5
      'app:elements:updateJson all',
    ];

    public static $messages = [
        // v2.3.0
        'A <b>Image (url)</b> field is now available in the form configuration!',
        "You can now customize the popup that is displayed when hovering over a marker. Go to Customize -> Marker / Popup",
        "New option for the menu (Customize -> The Map -> Menu tab): display next to each category the number of elements available for this category",
        // v2.3.1
        'You can now set the license that protects your data in Customization -> General Setup',
        // v2.3.4
        "Improvement of the <b>importing system</b>: you can now match fields and categories before importing them. We have made tutorial videos. <u>Please browse your dynamic imports to update them with the new system.</u>",
        "<b>User permissions management gets a makeover!</b> <u>Your old configuration may no longer be valid</u>. Please go to the <b>Users menu to update the roles of users and user groups.</b>",
        'You can now configure keywords to exclude in the search for items. Go to Customize -> The Map -> Search Tab',
        // v2.5
        "It is now possible to <b>upload images and files</b> from the add item form! Set up these new fields in Data Model -> Form",
        // v3.0
        "Now you can write news items that will be included in the automatic newsletter! Go to Mails/Newsletter -> News",
        "The exporting of elements from the page Data -> Elements page works again and this time correctly includes all custom fields (including files and images).",
        "From the site, the search by item now works on several fields. In Data Model -> Form, edit a field to see the configuration related to your search. You can also give different weights to each field, for example, the title search has a weight of 3 and the description search has a weight of 1.",
        "New search engine ! On the map, when you type a search, suggestions appear for items and categories (coming soon for geographical searches).",
        // v3.2
        "You can now configure the URL of your map if you have a domain name (or a subdomain). Go to Customize -> General Setup and follow the instructions!",
        "Login through a third party account (Google, Facebook, LesCommuns.org) is now possible! Change the configuration in Users -> Configuration",
        // 3.2.3
        "Notifications: you can now be alerted if an import has problems (to be configured in each Import) or if items are to be moderated (to be configured in Users)",
        // 3.3.0
        "Reading and writing data in OpenStreetMap is now possible through dynamic imports.",
        "GoGoCarto is now translated into different languages. If you want to contribute to the translation go to <a href=\"https://hosted.weblate.org/projects/gogocarto/\">the Weblate online tool</a>"
    ];

    public function __construct(DocumentManagerFactory $dmFactory, LoggerInterface $commandsLogger,
                               TokenStorageInterface $security,
                               AsyncService $asyncService)
    {
        $this->asyncService = $asyncService;
        $this->dm = $dmFactory->getRootManager();
        $this->dmFactory = $dmFactory;
        $this->logger = $commandsLogger;
        $this->security = $security;
        parent::__construct();
    }

    protected $count = 1;
    protected $current = 0;

    protected function configure(): void
    {
        $this->setName('db:migrate')
             ->setDescription('Update datatabse each time after code update'); // 
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $dm = $this->dm;
        $this->output = $output;
        $migrationState = $dm->query('MigrationState')->getQuery()->getSingleResult();
        if (null == $migrationState) { // Meaning the migration state was not yet in the place in the code
            $migrationState = new MigrationState();
            $dm->persist($migrationState);
        }

        try {
            // Collecting the Database to be updated
            $dbs = [$_ENV['DATABASE_NAME']]; // default DB
            $dbNames = $dm->query('Project')->select('domainName')->getArray();
            foreach ($dbNames as $dbName) $dbs[] = $dbName;
            $this->count = count($dbs);
            if (count(self::$migrations) > $migrationState->getMigrationIndex()) {
                $migrationsToRun = array_slice(self::$migrations, $migrationState->getMigrationIndex());
                $migrationsToRun = array_unique($migrationsToRun);
                $this->current = 0;
                foreach ($dbs as $db) {
                    foreach ($migrationsToRun as $migration) {
                        $this->log('run migration '.$migration, $db); // 
                        $this->runMongoCommand($dm, $db, $migration);
                        $this->current++;
                    }
                }
            } else {
                $this->log('No Migrations to perform'); // 
            }

            // run them syncronously otherwise all the command will be run at once
            $this->asyncService->setRunSynchronously(true);
            if (count(self::$commands) > $migrationState->getCommandsIndex()) {
                $commandsToRun = array_slice(self::$commands, $migrationState->getCommandsIndex());
                $commandsToRun = array_unique($commandsToRun);
                $this->current = 0;
                foreach ($dbs as $db) {
                    foreach ($commandsToRun as $command) {
                        $this->log('call command '.$command, $db); // 
                        $this->asyncService->callCommand($command, [], $db);
                        $this->current++;
                    }
                }
            } else {
                $this->log('No commands to run'); // 
            }

            if (count(self::$messages) > $migrationState->getMessagesIndex()) {
                $messagesToAdd = array_slice(self::$messages, $migrationState->getMessagesIndex());
                $this->current = 0;
                foreach ($dbs as $db) {
                    $this->log(count($messagesToAdd).' messages to add', $db); // 
                    foreach ($messagesToAdd as $message) {
                        // create a GoGoLogUpdate
                        $this->asyncService->callCommand('gogolog:add:message', ['"'.$message.'"'], $db); // 
                    }
                    $this->current++;
                }
            } else {
                $this->log('No Messages to add to dashboard'); // 
            }
        } catch (\Exception $e) {
            $message = $e->getMessage().'</br>'.$e->getFile().' LINE '.$e->getLine(); // 
            $this->error('Error performing migrations: '.$message); // 
        }

        $migrationState->setMigrationIndex(count(self::$migrations));
        $migrationState->setCommandsIndex(count(self::$commands));
        $migrationState->setMessagesIndex(count(self::$messages));
        $dm->flush();
    }

    private function runMongoCommand($dm, $dbName, $command)
    {
        $mongo = $dm->getConnection()->getMongoClient();
        $db = $mongo->selectDB($dbName);
        return $db->execute($command);
    }

    protected function log($message, $db = null)
    {
        if ($db) $message = "DB {$db} ($this->current/$this->count) : $message"; // 
        $this->logger->info($message);
        $this->output->writeln($message);
    }

    protected function error($message, $db = null)
    {
        if ($db) $message = "DB {$this->db} ($this->current/$this->count) : $message"; // 
        $this->logger->error($message);
        $this->output->writeln('ERROR '.$message);  // 
    }
}
