<?php

namespace App\Command;

use App\Services\AsyncService;
use App\Services\DocumentManagerFactory;
use App\EventListener\ConfigurationListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/*
* For SAAS Instance, this command run the daily commands
* one time every day for each project
*/
class ProjectUpdateCommand extends Command
{
    // List of the command to execute periodically
    public const DAILY_COMMANDS = [
      'app:elements:checkvote',
      'app:elements:checkExternalSourceToUpdate',
      'app:notify-moderation'
    ];

    public function __construct(DocumentManagerFactory $dmFactory, LoggerInterface $commandsLogger,
                                AsyncService $asyncService, ConfigurationListener $confService)
    {
        $this->dmFactory = $dmFactory;
        $this->asyncService = $asyncService;
        $this->logger = $commandsLogger;
        $this->confService = $confService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:project:update');
        $this->addArgument('dbname', InputArgument::OPTIONAL, 'Db name'); // 
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $rootDm = $this->dmFactory->getRootManager();

            // If project is specified, we update it, otherwise we get get project that need an update
            if ($input->getArgument('dbname')) {
                $project = $rootDm->get('Project')->findOneBy(['domainName' => $input->getArgument('dbname')]);
            } else {
                $project = $rootDm->query('Project')->field('nextUpdateAt')->lte(new \DateTime())
                                  ->sort('nextUpdateAt', 'ASC')
                                  ->getOne();
            }

            if ($project !== null) {
                $this->logger->info("---- PROJECT {$project->getDbName()} : Update infos & run daily commands");

                // Updating next execution time
                $dateNow = new \DateTime();
                $dateNow->setTimestamp(time());
                $interval = new \DateInterval('PT24H');
                $project->setNextUpdateAt($dateNow->add($interval));

                $rootDm->persist($project);
                $rootDm->flush();

                // Update Project Info - return false means the project is wrongly configured, like without config
                $result = $this->updateProjectInfo($project);

                $rootDm->flush();

                if (!$result) {
                    $this->logger->error('Error while update project info'); // 
                    return;   
                }             

                // run daily commands
                $this->asyncService->setRunSynchronously(true);
                foreach(self::DAILY_COMMANDS as $commandName) {
                    $this->asyncService->callCommand($commandName, [], $project->getDbName());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function updateProjectInfo($project)
    {
        try {
            $dm = $this->dmFactory->createForDB($project->getDbName());

            $config = $dm->get('Configuration')->findConfiguration();
            if (!$config) {
                $this->logger->error("Project {$project->getDomainName()} does not have config"); // 
                return false;
            }

            // ensure index are up to date
            // $dm->getSchemaManager()->updateIndexes();
            // $this->confService->manuallyUpdateIndex($dm);
            
            $img = $config->getSocialShareImage() ? $config->getSocialShareImage() : $config->getLogo();
            $imageUrl = $img ? $img->getImageUrl() : null;
            $dataSize = $dm->get('Element')->findVisibles(true);

            $qb = $dm->query('User');
            $users = $qb->addOr($qb->expr()->field('roles')->exists(true))
                        ->addOr($qb->expr()->field('groups')->exists(true))
                        ->getArray();
            $adminEmails = [];
            $lastLogin = null;
            foreach ($users as $key => $user) {
                if ($user->isAdmin()) $adminEmails[] = $user->getEmail();
                if (!$lastLogin || $user->getLastLogin() > $lastLogin) $lastLogin = $user->getLastLogin();
            }
            $haveWebhooks = $dm->query('Webhook')->count()->execute() > 0
                        || $dm->query('Import')->field('isSynchronized')->equals(true)->getCount() > 0;
            $haveNewsletter = $config->getNewsletterMail() && $config->getNewsletterMail()->getActive()
                        && $dm->query('User')->field('newsletterFrequency')->gt(0)->getCount() > 0;
            
            $project->setName($config->getAppName());
            $project->setImageUrl($imageUrl);
            $project->setDescription($config->getAppBaseline());
            $project->setDataSize($dataSize);
            $project->setAdminEmails(implode(',', $adminEmails));
            $project->setPublished($config->getPublishOnSaasPage());
            if ($lastLogin) $project->setLastLogin($lastLogin);
            else $project->setLastLogin($project->getCreatedAt());
            $project->setHaveWebhooks($haveWebhooks);
            $project->setHaveNewsletter($haveNewsletter);
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }
}
