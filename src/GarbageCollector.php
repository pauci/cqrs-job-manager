<?php

namespace CQRSJobManager;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\FingersCrossedHandler;

final class GarbageCollector
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var FingersCrossedHandler
     */
    private $fingersCrossedHandler;

    public function __construct(EntityManagerInterface $entityManager, FingersCrossedHandler $fingersCrossedHandler)
    {
        $this->entityManager         = $entityManager;
        $this->fingersCrossedHandler = $fingersCrossedHandler;
    }

    public function clear()
    {
        $this->entityManager->clear();
        $this->fingersCrossedHandler->clear();
    }
}
