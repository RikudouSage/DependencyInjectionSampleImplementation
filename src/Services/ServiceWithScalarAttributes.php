<?php

namespace Rikudou\DI\Services;

use Rikudou\DI\Annotations\Service;

/**
 * @Service(
 *     params={"someParameter"="someValue"}
 * )
 */
class ServiceWithScalarAttributes
{

    /**
     * @var Service1
     */
    private $service1;
    /**
     * @var string
     */
    private $someParameter;

    public function __construct(Service1 $service1, string $someParameter)
    {
        $this->service1 = $service1;
        $this->someParameter = $someParameter;
    }

    /**
     * @return string
     */
    public function getSomeParameter(): string
    {
        return $this->someParameter;
    }

}