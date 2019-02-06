<?php

namespace Rikudou\DI\Services;

use Rikudou\DI\Annotations\Service;

/**
 * @Service()
 */
class Service1
{
    /**
     * @var Service2
     */
    private $service2;

    /**
     * @var Service4
     */
    private $service4;

    public function __construct(Service2 $service2, Service4 $service4)
    {
        $this->service2 = $service2;
        $this->service4 = $service4;
    }

    /**
     * @return Service2
     */
    public function getService2(): Service2
    {
        return $this->service2;
    }

    /**
     * @return Service4
     */
    public function getService4(): Service4
    {
        return $this->service4;
    }
}
