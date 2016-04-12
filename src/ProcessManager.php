<?php

namespace CQRSJobManager;

use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\DBAL\Connection;
use ErrorException;
use Memcached;
use RuntimeException;

final class ProcessManager
{
    /**
     * @var Connection
     */
    private $connection;

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

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
        $this->closeResources();
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

    private function closeResources()
    {
        // Close the DB connection in preparation for forking
        $this->connection->close();

        $cache = $this->connection->getConfiguration()->getResultCacheImpl();

        if ($cache instanceof RedisCache) {
            $cache->getRedis()->close();
        } elseif ($cache instanceof MemcacheCache) {
            $cache->getMemcache()->close();
        } elseif ($cache instanceof MemcachedCache) {
            $servers = $cache->getMemcached()->getServerList();

            $memcached = new Memcached();
            $memcached->addServers($servers);
            $cache->setMemcached($memcached);
        }
    }
}
