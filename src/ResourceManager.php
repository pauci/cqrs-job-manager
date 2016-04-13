<?php

namespace CQRSJobManager;

class ResourceManager
{
    /**
     * @var array
     */
    private $resources = [];

    /**
     * @param array $resources
     */
    public function __construct(array $resources)
    {
        $this->resources = $resources;
    }

    public function close()
    {
        foreach ($this->resources as $resource) {
            if ($resource instanceof \Redis) {
                $resource->close();
            } elseif ($resource instanceof \Memcache) {
                $resource->close();
            } elseif ($resource instanceof \Memcached) {
                $resource->quit();
            } elseif ($resource instanceof \Doctrine\DBAL\Connection) {
                $resource->close();
            }
        }
    }
}
