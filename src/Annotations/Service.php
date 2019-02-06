<?php

namespace Rikudou\DI\Annotations;

/**
 * This annotation is used to configure services, when a class has this annotation
 * it's a service.
 *
 * The params is used to provide manual configuration for parameter, it can be used for scalar values
 * but also to overwrite the service that will be injected. If overwriting injected service, it must be a string
 * that starts with the @ character, otherwise it will be treated as a string.
 *
 * @Annotation
 */
class Service
{
    /**
     * @var array
     */
    public $params = [];

    /**
     * @var string
     */
    public $name = null;
}
