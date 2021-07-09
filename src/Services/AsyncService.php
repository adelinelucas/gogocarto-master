<?php

namespace App\Services;

use App\Services\DocumentManagerFactory;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class AsyncService
{
    protected $runSynchronously = false;

    public function __construct(DocumentManagerFactory $dmFactory, Filesystem $filesystem, $rootDir, $env)
    {
        $this->dmFactory = $dmFactory;
        $this->filesystem = $filesystem;
        $this->rootDir = $rootDir;
        $this->env = $env;
        $this->consolePath = 'bin/console';
        $this->consolePath = $this->resolveConsolePath();
        $this->phpPath = $this->resolvePhpPath();
    }

    public function setRunSynchronously($bool)
    {
        $this->runSynchronously = $bool;
    }

    public function callCommand($commandName, $arguments = [], $dbname = null)
    {
        if (null === $dbname) {
            $dbname = $this->dmFactory->getCurrentDbName();
        }

        $commandline = $this->phpPath.' -d memory_limit=-1 '.$this->consolePath;
        $commandline .= ' --env='.$this->env;
        $commandline .= ' '.$commandName;
        foreach ($arguments as $key => $arg) {
            $commandline .= ' '.$arg;
        }
        $commandline .= ' '.$dbname;
        if (!$this->runSynchronously) {
            $commandline .= ' &';
        }

        return $this->runProcess($commandline);
    }

    protected function runProcess($commandline)
    {
        $process = Process::fromShellCommandline($commandline);
        $process->disableOutput();
        $process->setTimeout(0);
        $process->run();

        return $process->getPid();
    }

    protected function resolveConsolePath()
    {
        $consolePath = $this->consolePath;

        if (!$this->filesystem->isAbsolutePath($consolePath)) {
            $consolePath = $this->rootDir.'/../'.$consolePath;
        }

        if (!$this->filesystem->exists($consolePath)) {
            throw new FileNotFoundException(sprintf('File %s doesn\'t exist', $consolePath));
        }

        return $consolePath;
    }

    protected function resolvePhpPath()
    {
        $finder = new PhpExecutableFinder();
        $phpPath = $_ENV['PHP_PATH'] ?? $finder->find();

        if (!$phpPath) {
            throw new FileNotFoundException(sprintf('PHP executable doesn\'t exist'));
        }

        return $phpPath;
    }
}
