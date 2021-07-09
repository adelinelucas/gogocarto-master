<?php

namespace App\Command;

use App\Document\GoGoLog;
use App\Document\GoGoLogLevel;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Services\DocumentManagerFactory;

class GoGoAbstractCommand extends Command
{
    protected $dm;
    protected $dmFactory;
    protected $logger;
    protected $security;
    protected $output;
    protected $runOnlyOnRootDatabase = false;

    public function __construct(DocumentManagerFactory $dmFactory, LoggerInterface $commandsLogger,
                               TokenStorageInterface $tokenStorage)
    {
        $this->dmFactory = $dmFactory;
        $this->logger = $commandsLogger;
        $this->security = $tokenStorage;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:abstract:command');
        $this->gogoConfigure();
        $this->addArgument('dbname', InputArgument::OPTIONAL, 'Db name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->dmFactory->getCurrentManager();
        try {
            $this->output = $output;

            // create dummy user, as some code called from command will maybe need the current user informations
            $token = new AnonymousToken('admin', 'GoGoBot', ['ROLE_ADMIN']);
            $this->security->setToken($token);

            if ($this->runOnlyOnRootDatabase) {
                $this->dm = $this->dmFactory->getRootManager();
                $this->gogoExecute($this->dm, $input, $output);
            } else if ($input->getArgument('dbname')) {
                $this->dm = $this->dmFactory->switchCurrManagerToUseDb($input->getArgument('dbname'));
                $this->gogoExecute($this->dm, $input, $output);
            } else if ($_ENV['USE_AS_SAAS'] === 'true') {
                $qb = $this->dm->query('Project');
                $this->filterProjects($qb);
                $dbs = $qb->select('domainName')->getArray();
                $count = count($dbs);
                $this->log("---- Run {$this->getName()} for $count projects", false);
                foreach($dbs as $dbName) {
                    $this->dm = $this->dmFactory->createForDB($dbName);;
                    $this->gogoExecute($this->dm, $input, $output);
                }
            } else {
                $this->dm = $this->dmFactory->getRootManager();
                $this->gogoExecute($this->dm, $input, $output);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage().'</br>'.$e->getFile().' LINE '.$e->getLine();
            $this->error('Error executing command: '.$message);
        }
        return 1;
    }

    protected function gogoExecute(DocumentManager $dm, InputInterface $input, OutputInterface $output): void
    {
    }

    protected function gogoConfigure(): void
    {
    }

    // when calling the command with dbName, we run it for all projects
    // Here we cna filter the project that really need to be processed
    protected function filterProjects($qb)
    {
    }

    protected function log($message, $usePrefix = true)
    {
        if ($usePrefix) $message = "DB {$this->dm->getConfiguration()->getDefaultDB()} : $message";
        $this->logger->info($message);
        $this->output->writeln($message);
    }

    protected function error($message)
    {
        $log = new GoGoLog(GoGoLogLevel::Error, 'Error running '.$this->getName().' : '.$message);
        $this->dm->persist($log);
        $message = "DB {$this->dm->getConfiguration()->getDefaultDB()} : $message";
        $this->logger->error($message);
        $this->output->writeln('ERROR '.$message);
        $this->dm->flush();
    }
}
