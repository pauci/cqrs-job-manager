<?php

namespace CQRSJobManager;

use ErrorException;
use RuntimeException;

class ProcessManager
{
    /**
     * @var ResourceManager
     */
    private $resourceManager;

    /**
     * @var bool
     */
    private $childSignalHandlerInstalled = false;

    /**
     * @var array
     */
    private $childSignalQueue = [];

    /**
     * @var callable[]
     */
    private $childSignalHandlers = [];

    /**
     * @param ResourceManager $resourceManager
     */
    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * @return int
     */
    public function getMyPid()
    {
        return getmypid();
    }

    /**
     * @param string|null $title
     * @return int
     * @throws RuntimeException
     */
    public function fork($title = null)
    {
        // Close resources prior to forking
        $this->resourceManager->close();

        $this->installChildSignalHandler();

        set_error_handler(function ($code, $message, $file, $line) {
            $error = new ErrorException($message, $code, 1, $file, $line);
            throw new RuntimeException('Failed to fork process', 0, $error);
        });

        $pid = pcntl_fork();

        restore_error_handler();

        if ($pid === 0 && null !== $title) {
            cli_set_process_title($title);
        }

        return $pid;
    }

    /**
     * @param int $pid
     */
    public function kill($pid)
    {
        posix_kill($pid, SIGTERM);
    }

    /**
     * @param int $pid
     * @param callable $handler
     */
    public function installChildExitHandler($pid, callable $handler)
    {
        $this->childSignalHandlers[$pid] = $handler;
        $this->handleQueuedChildSignals($pid);
    }

    /**
     * @param callable $handler
     */
    public function installTerminationSignalHandler(callable $handler)
    {
        foreach ([SIGTERM, SIGINT, SIGHUP] as $signal) {
            pcntl_signal($signal, $handler);
        }
    }

    public function dispatchSignals()
    {
        pcntl_signal_dispatch();
    }

    private function installChildSignalHandler()
    {
        if ($this->childSignalHandlerInstalled) {
            return;
        }

        $this->childSignalHandlerInstalled = pcntl_signal(SIGCHLD, function () {
            $pid = pcntl_wait($status, WNOHANG);
            while ($pid > 0) {
                $this->childSignalQueue[$pid][] = $status;
                $this->handleQueuedChildSignals($pid);

                $pid = pcntl_wait($status, WNOHANG);
            }
        });
    }

    /**
     * @param int $pid
     */
    private function handleQueuedChildSignals($pid)
    {
        if (!isset($this->childSignalHandlers[$pid], $this->childSignalQueue[$pid])) {
            return;
        }

        $status = array_shift($this->childSignalQueue[$pid]);
        if (pcntl_wifexited($status)) {
            $exitCode = pcntl_wexitstatus($status);
            $this->childSignalHandlers[$pid]($exitCode);
        } elseif (pcntl_wifsignaled($status)) {
            $this->childSignalHandlers[$pid](0);
        } elseif (pcntl_wifstopped($status)) {
            posix_kill($pid, SIGCONT);
        }
    }
}
