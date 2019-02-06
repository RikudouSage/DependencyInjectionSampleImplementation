<?php

namespace Rikudou\DI\Services;

use Rikudou\DI\Annotations\Service;

/**
 * @Service()
 */
class Service2
{
    /**
     * @var Service3
     */
    private $service3;

    public function __construct(Service3 $service3)
    {
        $this->service3 = $service3;
    }

    /**
     * @return Service3
     */
    public function getService3(): Service3
    {
        return $this->service3;
    }
}
