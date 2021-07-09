<?php

namespace App\Command;

use App\Services\ElementVoteService;
use App\Services\UserNotificationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Services\DocumentManagerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CheckVoteCommand extends GoGoAbstractCommand
{
    public function __construct(DocumentManagerFactory $dm, LoggerInterface $commandsLogger,
                               TokenStorageInterface $security,
                               ElementVoteService $voteService,
                               UserNotificationService $notifService)
    {
        $this->voteService = $voteService;
        $this->notifService = $notifService;
        parent::__construct($dm, $commandsLogger, $security);
    }

    protected function gogoConfigure(): void
    {
        $this
        ->setName('app:elements:checkvote')
        ->setDescription('Check for collaborative vote validation')
    ;
    }

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
        $config = $dm->get('Configuration')->findConfiguration();
        if (!$config || !$config->getCollaborativeModerationFeature()->getActive()) return;

        $elements = $dm->get('Element')->findPendings();
        if (count($elements)) {
            $this->log('Checking Vote for '.count($elements) . ' elements');
            $i = 0;
            foreach ($elements as $element) {
                $this->voteService->checkVotes($element, $dm);
                $dm->persist($element);
                if (0 == (++$i % 20)) {
                    $dm->flush();
                    $dm->clear();
                }
            }
            $dm->flush();           
        }
    }
}
