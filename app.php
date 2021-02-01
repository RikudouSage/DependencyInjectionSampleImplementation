<?php

use Rikudou\DI\ServiceLocator;
use Rikudou\DI\Services\NamedService;
use Rikudou\DI\Services\Service1;
use Rikudou\DI\Services\Service2;
use Rikudou\DI\Services\Service3;
use Rikudou\DI\Services\Service4;
use Rikudou\DI\Services\ServiceWithScalarAttributes;

require_once __DIR__ . "/vendor/autoload.php";

$serviceLocator = new ServiceLocator();

// get the Service1 from service locator
// when no service name is configured, it defaults to the class name
$service1 = $serviceLocator->get(Service1::class);
$serviceWithScalarAttributes = $serviceLocator->get(ServiceWithScalarAttributes::class);

// these assertions all should be true
assert($service1 instanceof Service1);
assert($service1->getService2() instanceof Service2);
assert($service1->getService2()->getService3() instanceof Service3);
assert($service1->getService4() instanceof Service4);
assert($service1->getService2()->getService3()->getService4() instanceof Service4);
assert($service1->getService4() === $service1->getService2()->getService3()->getService4());
assert($service1->getService4()->getNamedService() instanceof NamedService);
assert($serviceWithScalarAttributes instanceof ServiceWithScalarAttributes);

// Rikudou\DI\Services\Service1
var_dump(get_class($service1));
// Rikudou\DI\Services\Service2
var_dump(get_class($service1->getService2()));
// Rikudou\DI\Services\Service3
var_dump(get_class($service1->getService2()->getService3()));
// Rikudou\DI\Services\Service4
var_dump(get_class($service1->getService4()));
// Rikudou\DI\Services\ServiceWithScalarAttributes
var_dump(get_class($serviceWithScalarAttributes));
// "someValue" as defined in service configuration
var_dump($serviceWithScalarAttributes->getSomeParameter());
