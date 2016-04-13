<?php

namespace CQRSJobManager\Job;

use CQRSJobManager\EventProcessor;
use CQRS\Domain\Message\EventMessageInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Generator;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use Throwable;

/**
 * @ORM\Entity(repositoryClass="CQRSJobManager\Infrastructure\Doctrine\Repository\DoctrineJobWorkerRepository")
 * @ORM\Table(name="cqrs_job_worker")
 */
class JobWorker implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="job_name")
     * @var JobName
     */
    private $jobName;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $running = false;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @var int
     */
    private $eventsCount = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @var int
     */
    private $errorsCount = 0;

    /**
     * @ORM\Column(type="uuid")
     * @var UuidInterface
     */
    private $lastEventId;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $lastEventTime;

    /**
     * @ORM\OneToOne(targetEntity="JobError", cascade={"persist"})
     * @var JobError
     */
    private $lastError;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $startedAt;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $lastUpdate;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @ORM\Version
     * @var int
     */
    private $version;

    /**
     * @var bool
     */
    private $terminate = false;

    public function start(
        EventProcessor $eventProcessor,
        JobSettings $settings,
        UuidInterface $previousEventId = null
    ) : Generator {
        $this->lock();

        if ($previousEventId !== null) {
            $this->lastEventId = $previousEventId;
        }
        yield; // allow to persist worker state

        // Run
        while (!$this->terminate && $this->running) {
            foreach ($this->run($eventProcessor, $settings) as $event) {
                yield $event; // allow to persist worker state after each processed event
            }
        }

        $this->unlock();
    }

    public function stop()
    {
        if ($this->running) {
            $this->terminate = true;
        }
    }

    private function lock()
    {
        if ($this->running) {
            throw new RuntimeException(sprintf('Job %s is already running', $this->jobName));
        }

        $this->running = true;
        $this->eventsCount = 0;
        $this->errorsCount = 0;
        $this->startedAt = new DateTime();
        $this->lastUpdate = new DateTime();
    }

    private function unlock()
    {
        $this->running = false;
        $this->terminate = false;
        $this->lastUpdate = new DateTime();
    }

    private function run(EventProcessor $eventProcessor, JobSettings $settings) : Generator
    {
        try {
            $process = $eventProcessor->process($settings, $this->lastEventId);

            /** @var EventMessageInterface $event */
            foreach ($process as $event) {
                $this->trackEvent($event);
                yield $event;

                if ($this->terminate) {
                    break;
                }
            }
        } catch (Throwable $e) {
            $lastEventId = $eventProcessor->getLastEventId();
            $this->trackError($lastEventId, $e);

            if ($lastEventId === null || $lastEventId->equals($this->lastEventId) || $settings->isStopOnError()) {
                $this->stop();
            } else {
                $this->lastEventId = $lastEventId;
                yield;
            }
        }
    }

    private function trackEvent(EventMessageInterface $event)
    {
        $this->eventsCount++;
        $this->lastEventId = $event->getId();
        $this->lastEventTime = $event->getTimestamp();
        $this->lastUpdate = new DateTime();
    }

    private function trackError(UuidInterface $lastEventId, Throwable $e)
    {
        $this->lastError = JobError::create($this->jobName, $lastEventId, $e);
        $this->errorsCount++;
    }

    public function jsonSerialize() : array
    {
        return [
            'jobName' => $this->jobName,
            'running' => $this->running,
            'eventsCount' => $this->eventsCount,
            'errorsCount' => $this->errorsCount,
            'lastEventId' => $this->lastEventId,
            'lastEventTime' => $this->lastEventTime,
            'lastError' => $this->lastError,
            'startedAt' => $this->startedAt,
            'lastUpdate' => $this->lastUpdate,
        ];
    }
}
