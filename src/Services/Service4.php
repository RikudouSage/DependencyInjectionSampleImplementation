<?php

namespace Rikudou\DI\Services;

use Rikudou\DI\Annotations\Service;

/**
 * As the NamedService class defines its own name, it cannot be autowired without some configuration.
 * Here we configure that the parameter $namedService should be instance of "my.named.service" which
 * is the NamedService class.
 *
 * @Service(
 *     params={"namedService"="@my.named.service"}
 * )
 */
class Service4
{
    /**
     * @var NamedService
     */
    private $namedService;

    public function __construct(NamedService $namedService)
    {
        $this->namedService = $namedService;
    }

    /**
     * @return NamedService
     */
    public function getNamedService(): NamedService
    {
        return $this->namedService;
    }
}
