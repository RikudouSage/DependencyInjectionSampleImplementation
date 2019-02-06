<?php

namespace Rikudou\DI\Services;

use Rikudou\DI\Annotations\Service;

/**
 * If we don't define this attribute here, the service locator will throw an exception
 * that it doesn't know how to inject parameter of type "string"
 *
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
