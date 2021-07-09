<?php

namespace App\Command;

use App\Document\ImportState;
use App\Services\ElementImportService;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Services\DocumentManagerFactory;
use App\Services\UserNotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Document\GoGoLogImport;
use App\Document\GoGoLogLevel;

class ImportSourceCommand extends GoGoAbstractCommand
{
    public function __construct(DocumentManagerFactory $dm, LoggerInterface $commandsLogger,
                               TokenStorageInterface $security,
                               ElementImportService $importService,
                               UserNotificationService $notifService)
    {
        $this->importService = $importService;
        $this->notifService = $notifService;
        parent::__construct($dm, $commandsLogger, $security);
    }

    protected function gogoConfigure(): void
    {
        $this
        ->setName('app:elements:importSource')
        ->setDescription('Check for updating external sources')  // 
        ->addArgument('sourceNameOrImportId', InputArgument::REQUIRED, 'The name of the source') // 
        ->addArgument('manuallyStarted', InputArgument::REQUIRED, 'Started by a user from the UI or by gogocarto crontab'); // 
    }

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->output = $output;
            $sourceNameOrId = $input->getArgument('sourceNameOrImportId');
            $import = $dm->get('Import')->find($sourceNameOrId);
            if (!$import) {
                $import = $dm->get('Import')->findOneBy(['sourceName' => $sourceNameOrId]);
            }
            if (!$import) {
                $message = $this->t('importService.error_no_source', ['source' => $input->getArgument('sourceNameOrImportId')], 'admin');
                $this->error($message); 
                return;
            }
            $this->log("Updating source {$import->getSourceName()} begins..."); // 
            $result = $this->importService->startImport($import, $input->getArgument('manuallyStarted'));
            $this->log($result);
        } catch (\Exception $e) {
            $this->dm->persist($import);
            $import->setCurrState(ImportState::Failed);
            $message = $e->getMessage().'</br>'.$e->getFile().' LINE '.$e->getLine();
            $import->setCurrMessage($message);
            $log = new GoGoLogImport(GoGoLogLevel::Error, $message, []);            
            $this->dm->persist($log);
            $this->dm->flush();
            $import->addLog($log);
            $this->error("Source: {$import->getSourceName()} - $message");
            $this->notifService->notifyImportError($import);
        }
    }
}
