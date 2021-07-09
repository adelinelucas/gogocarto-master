<?php

namespace App\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use App\Services\DocumentManagerFactory;
use App\Services\MailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class NewsletterCommand extends GoGoAbstractCommand
{
    public function __construct(DocumentManagerFactory $dm, LoggerInterface $commandsLogger,
                               TokenStorageInterface $security, MailService $mailService)
    {
        parent::__construct($dm, $commandsLogger, $security);
        $this->mailService = $mailService;
        $this->remainingEmailsCount = $_ENV['MAX_EMAIL_PER_HOUR'];
    }

    protected function gogoConfigure(): void
    {
        $this
          ->setName('app:users:sendNewsletter')
          ->setDescription('Send the newsletter to each user') // 
       ;
    }

    protected function filterProjects($qb)
    {
        return $qb->field('haveNewsletter')->equals(true);
    }

    private $remainingEmailsCount = 0;

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
        if ($this->remainingEmailsCount <= 0) return; 

        $users = $dm->get('User')->findNeedsToReceiveNewsletter($this->remainingEmailsCount);
        $usersCount = count($users);
        $this->remainingEmailsCount -= $usersCount;

        if ($usersCount)
            $this->log("Send Newsletter for $usersCount users"); // 

        foreach ($users as $user) {
            $dm->persist($user);
            $elements = $dm->get('Element')->findWithinCenterFromDate(
                $user->getGeo()->getLatitude(),
                $user->getGeo()->getLongitude(),
                $user->getNewsletterRange(),
                $user->getLastNewsletterSentAt());

            $elementCount = $elements->count();
            if ($elementCount > 0) {
                $this->mailService->sendAutomatedMail('newsletter', $user, null, $elements);
            }
            $user->setLastNewsletterSentAt(new \DateTime());
            $user->updateNextNewsletterDate();
        }
        $dm->flush();
    }
}
