<?php

namespace App\Command;

use App\Services\RandomCreationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Services\DocumentManagerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GenerateElementsCommand extends GoGoAbstractCommand
{
    public function __construct(DocumentManagerFactory $dm, LoggerInterface $commandsLogger,
                               TokenStorageInterface $security,
                               RandomCreationService $randomService)
    {
        $this->randomService = $randomService;
        parent::__construct($dm, $commandsLogger, $security);
    }

    protected function gogoConfigure(): void
    {
        $this
        ->setName('app:elements:generate')
        ->setDescription('Generate random elements.') 
        ->setHelp('This command allows you generate random elements')  
        ->addArgument('number', InputArgument::REQUIRED, 'The number of generated elements')
        ->addArgument('generateVotes', InputArgument::OPTIONAL, 'If we generate somes fake votes') 
    ;
    }

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;

        $randomService->generate($input->getArgument('number'), $input->getArgument('generateVotes'));

        $this->log('Elements generated !');
    }
}
