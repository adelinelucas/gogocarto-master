<?php

namespace App\Command;

use App\Services\ElementImportService;
use App\Services\UserNotificationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Services\DocumentManagerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Console\Input\ArrayInput;

class CheckExternalSourceToUpdateCommand extends GoGoAbstractCommand
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
        ->setName('app:elements:checkExternalSourceToUpdate')
        ->setDescription('Check for updating external sources'); 
    }

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
        $dynamicImports = $dm->query('ImportDynamic')
                ->field('refreshFrequencyInDays')->gt(0)
                ->field('nextRefresh')->lte(new \DateTime())
                ->getCursor();

        if ($count = $dynamicImports->count() > 0) {
            $this->log("CheckExternalSourceToUpdate : # sources to update : $count"); 

            $command = $this->getApplication()->find('app:elements:importSource');            

            foreach ($dynamicImports as $import) {
                $arguments = new ArrayInput([
                    'sourceNameOrImportId' => $import->getId(),
                    'manuallyStarted'  => false,
                    'dbname' => $input->getArgument('dbname')
                ]);
                $command->run($arguments, $output);
            }
        }
    }
}
