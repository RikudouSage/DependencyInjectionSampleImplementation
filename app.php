<?php

use Rikudou\DI\ServiceLocator;
use Rikudou\DI\Services\Service1;
use Rikudou\DI\Services\Service2;
use Rikudou\DI\Services\Service3;
use Rikudou\DI\Services\Service4;

require_once __DIR__ . "/vendor/autoload.php";

// get the Service1 from service locator
// when no service name is configured, it defaults to the class name
$service1 = ServiceLocator::getService(Service1::class);

// these assertions all should be true
// as all the services are only one instance, the last assertion also equals true
assert($service1 instanceof Service1);
assert($service1->getService2() instanceof Service2);
assert($service1->getService2()->getService3() instanceof Service3);
assert($service1->getService4() instanceof Service4);
assert($service1->getService2()->getService3()->getService4() instanceof Service4);
assert($service1->getService4() === $service1->getService2()->getService3()->getService4());

// Rikudou\DI\Services\Service1
var_dump(get_class($service1));
// Rikudou\DI\Services\Service2
var_dump(get_class($service1->getService2()));
// Rikudou\DI\Services\Service3
var_dump(get_class($service1->getService2()->getService3()));
// Rikudou\DI\Services\Service4
var_dump(get_class($service1->getService4()));
