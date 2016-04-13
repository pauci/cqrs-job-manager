<?php

namespace CQRSJobManager\Job;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * @ORM\Entity
 * @ORM\Table(name="backend_job_error")
 */
class JobError implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="job_name")
     * @var JobName
     */
    private $jobName;

    /**
     * @ORM\Column(type="uuid")
     * @var UuidInterface
     */
    private $eventId;

    /**
     * @ORM\Column(length=1000)
     * @var string
     */
    private $message;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $time;

    public static function create(JobName $jobName, UuidInterface $eventId, Throwable $e) : JobError
    {
        return new self(
            $jobName,
            $eventId,
            $e->getMessage(),
            new DateTime()
        );
    }

    private function __construct(JobName $jobName, UuidInterface $eventId, string $message, DateTime $time)
    {
        $this->jobName = $jobName;
        $this->eventId = $eventId;
        $this->message = $message;
        $this->time = $time;
    }

    public function jsonSerialize() : array
    {
        return [
            'id' => $this->id,
            'jobName' => $this->jobName,
            'eventId' => $this->eventId,
            'message' => $this->message,
            'time' => $this->time->format(DateTime::ATOM),
        ];
    }
}
