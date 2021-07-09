<?php

namespace App\Command;

use App\Document\ModerationState;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRemoveCommand extends GoGoAbstractCommand
{
    protected function gogoConfigure(): void
    {
        $this
            ->setName('app:import:remove')
            ->addArgument('importId', InputArgument::REQUIRED, 'The name of the source')
       ;
    }

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
        try {
            $importId = $input->getArgument('importId');     
            $this->log('Removing elements from import ' . $importId);
            $dm->query('Element')->field('source.$id')->equals((int) $importId)->batchRemove();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
