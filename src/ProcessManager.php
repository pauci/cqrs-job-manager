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
    private $queueingChildSignals = false;

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
     * Returns the current process ID
     *
     * @return int
     */
    public function getMyPid() : int
    {
        return getmypid();
    }

    /**
     * Creates a child process. Returns child PID in parent process, 0 in child process.
     *
     * @param string|null $processTitle
     * @return int
     * @throws RuntimeException
     */
    public function forkChild(string $processTitle = null) : int
    {
        // Close resources prior to forking
        $this->resourceManager->closeResources();

        // Start queueing child signals prior to forking, so no signal gets lost
        $this->startQueueingChildSignals();

        set_error_handler(function ($code, $message, $file, $line) {
            $error = new ErrorException($message, $code, 1, $file, $line);
            throw new RuntimeException('Failed to fork process', 0, $error);
        });

        $pid = pcntl_fork();

        restore_error_handler();

        if ($pid === 0 && null !== $processTitle) {
            cli_set_process_title($processTitle);
        }

        return $pid;
    }

    /**
     * Sends termination signal
     *
     * @param int $pid
     */
    public function kill(int $pid)
    {
        posix_kill($pid, SIGTERM);
    }

    /**
     * Registers handler to be called when child exits
     *
     * @param int $childPid
     * @param callable $handler
     */
    public function onChildExited(int $childPid, callable $handler)
    {
        $this->childSignalHandlers[$childPid] = $handler;
        $this->handleQueuedChildSignals($childPid);
    }

    /**
     * Registers handler to be called when termination signal is received
     *
     * @param callable $handler
     */
    public function onTermination(callable $handler)
    {
        foreach ([SIGTERM, SIGINT, SIGHUP] as $signal) {
            pcntl_signal($signal, $handler);
        }
    }

    /**
     * Dispatch registered handlers for any pending signals
     */
    public function dispatchSignals()
    {
        pcntl_signal_dispatch();
    }

    private function startQueueingChildSignals()
    {
        if (!$this->queueingChildSignals) {
            $this->queueingChildSignals = pcntl_signal(SIGCHLD, [$this, 'queueChildSignal']);
        }
    }

    private function queueChildSignal()
    {
        while (true) {
            $childPid = pcntl_wait($status, WNOHANG);
            if ($childPid <= 0) {
                return;
            }

            // Add child signal to queue
            $this->childSignalQueue[$childPid][] = $status;
            // Handle any child signals queued
            $this->handleQueuedChildSignals($childPid);
        }
    }

    /**
     * @param int $childPid
     */
    private function handleQueuedChildSignals(int $childPid)
    {
        if (!isset($this->childSignalHandlers[$childPid], $this->childSignalQueue[$childPid])) {
            return;
        }

        $status = array_shift($this->childSignalQueue[$childPid]);
        if (pcntl_wifexited($status)) {
            $exitCode = pcntl_wexitstatus($status);
            $this->childSignalHandlers[$childPid]($exitCode);
        } elseif (pcntl_wifsignaled($status)) {
            $this->childSignalHandlers[$childPid](0);
        } elseif (pcntl_wifstopped($status)) {
            posix_kill($childPid, SIGCONT);
        }
    }
}
