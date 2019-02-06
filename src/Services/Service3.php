<?php

namespace Rikudou\DI\Services;

use Rikudou\DI\Annotations\Service;

/**
 * @Service()
 */
class Service3
{
    /**
     * @var Service4
     */
    private $service4;

    public function __construct(Service4 $service4)
    {
        $this->service4 = $service4;
    }

    /**
     * @return Service4
     */
    public function getService4(): Service4
    {
        return $this->service4;
    }
}
