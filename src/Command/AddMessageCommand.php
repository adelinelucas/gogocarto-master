<?php

namespace App\Command;

use App\Document\GoGoLogUpdate;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to update database when schema need migration
 * Also provide some update message in the admin dashboard.
 */
class AddMessageCommand extends GoGoAbstractCommand
{
    protected function gogoConfigure(): void
    {
        $this->setName('gogolog:add:message')
             ->addArgument('message', InputArgument::REQUIRED, 'Message to add') 
             ->setDescription('Update database each time after code update'); 
    }

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
        $log = new GoGoLogUpdate('info', $input->getArgument('message'));

        $dm->persist($log);
        $dm->flush();
    }
}
