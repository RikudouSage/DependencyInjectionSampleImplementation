<?php

namespace Rikudou\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Rikudou\DI\Annotations\Service;
use Rikudou\DI\Exception\ServiceArgumentException;
use Rikudou\DI\Exception\ServiceNotFoundException;

/**
 * Really simple and dirty implementation of service locator with DI.
 *
 * It should not be used anywhere near production.
 *
 * The implementation is incomplete, for example it does not check whether the service is instantiable, does not
 * support interfaces, it isn't cached at all which makes it really slow as the service configuration is
 * reloaded every time using Reflection, it supports only constructor injection, doesn't check for circular
 * dependencies etc.
 *
 * Also the class is not the most organized piece of code I ever wrote but as it's just an example for learning
 * I think it works.
 */
class ServiceLocator
{
    /**
     * Holds the instance used for static access to service locator
     *
     * @var ServiceLocator|null
     */
    private static $instance = null;

    /**
     * Holds the configuration of services, e.g. service name, class, parameters configuration etc.
     *
     * @var array
     */
    private $serviceDefinitions = [];

    /**
     * Holds the instantiated services
     *
     * @var array
     */
    private $services = [];

    /**
     * Marked as internal as the ServiceLocator should be used statically
     *
     * @internal
     */
    public function __construct()
    {
        AnnotationRegistry::registerLoader('class_exists');
    }

    /**
     * @param string $service
     *
     * @throws ServiceArgumentException
     * @throws ServiceNotFoundException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     *
     * @return object
     */
    public function get(string $service)
    {
        // parses all php files and finds services in these files
        // if there already are service definitions, the method does nothing
        $this->locateServices();

        // if the service is already constructed, just return it
        if (isset($this->services[$service])) {
            return $this->services[$service];
        }
        // if it's not constructed do it now
        $this->constructService($service);

        // this shouldn't really happen
        if (!isset($this->services[$service])) {
            throw new \LogicException('The service was not constructed during constructService() call');
        }

        // return the constructed service
        return $this->services[$service];
    }

    public static function getService(string $service)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance->get($service);
    }

    /**
     * @throws ServiceArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    private function locateServices()
    {
        // if there are service definitions, skip this step as the services were already loaded
        if (count($this->serviceDefinitions)) {
            return;
        }

        $parser = new AnnotationReader();

        // traverse all php files to check if  any of them contains a service
        foreach ($this->getPhpFiles() as $file) {
            // parses the php file and returns info about class (if the file contains class)
            $finder = new ClassFinder($file->getRealPath());
            // we only care about classes, if the file does not contain class, ignore it
            if ($finder->isValidClass()) {
                // annotation reader works with reflection
                $class = $finder->getReflection();
                // gets the service annotation which is used to determine whether the class
                // is a service or not
                $serviceAnnotation = $parser->getClassAnnotation($class, Service::class);
                // if the getClassAnnotation() returns null, it did not find the annotation on
                // checked class and we can ignore it
                if (!is_null($serviceAnnotation)) {
                    // the annotation is an instance of Service, this assert is here to check
                    // that no strange logic error happened and also to help IDE completion
                    assert($serviceAnnotation instanceof Service);
                    // the service could define its own name
                    if (!$name = $serviceAnnotation->name) {
                        // if no name was defined, use the class name as service name
                        $name = $class->getName();
                    }

                    // this array holds the configuration for the service
                    $config = [
                        'parameters' => [],
                    ];

                    // the service can overwrite its own parameters via the annotation
                    $configuredParams = $serviceAnnotation->params ?? [];

                    // we support only constructor injection, we need to get the constructor
                    $constructor = $class->getConstructor();
                    // if the constructor is null, the class does not have one and we can ignore the parameter
                    // configuration as there are no injectable parameters
                    if (!is_null($constructor)) {
                        // iterate over all the parameters
                        $parameters = $constructor->getParameters();
                        foreach ($parameters as $parameter) {
                            // the type from reflection, it can be a fully qualified class name
                            // or it can be a built-in type like 'string', 'array' etc.
                            $type = $parameter->getType()->getName();
                            // here we check whether the service configured this parameter in the annotation
                            if (isset($configuredParams[$parameter->getName()])) {
                                // fetch the value from configured parameters to check its type
                                $value = $configuredParams[$parameter->getName()];
                                // if the value is a string and starts with @ we can assume it's a service
                                if (is_string($value) && substr($value, 0, 1) === '@') {
                                    // configure the parameter as a service and remove the @ from its value
                                    $config['parameters'][$parameter->getName()] = [
                                        'type' => 'service',
                                        'value' => substr($value, 1),
                                    ];
                                // if it's anything else than string starting with @ we just mark this as preconfigured
                                // and inject its value directly to service definition
                                } else {
                                    $config['parameters'][$parameter->getName()] = [
                                        'type' => 'preconfigured',
                                        'value' => $configuredParams[$parameter->getName()],
                                    ];
                                }
                            // if the class did not configure this parameter, we check whether it's a class
                            } elseif (class_exists($type)) {
                                // if it is a class, we mark this parameter as service
                                // we could check whether the class is a service (contains @Service annotation)
                                // but it's handled when constructing a service
                                $config['parameters'][$parameter->getName()] = [
                                    'type' => 'service',
                                    'value' => $type,
                                ];
                            // if the parameter is not a valid class, we don't know what to do with it
                            // so just throw an exception telling the user that the parameter cannot be autowired
                            } else {
                                throw new ServiceArgumentException("The argument of type '{$type}' cannot be autowired");
                            }
                        }
                    }

                    // add the class name to configuration as the service name
                    // does not have to be the same as class name
                    $config['class'] = $class->getName();
                    // store the configuration in service definitions array under the service name
                    $this->serviceDefinitions[$name] = $config;
                }
            }
        }
    }

    /**
     * @param string $serviceName
     *
     * @throws ServiceArgumentException
     * @throws ServiceNotFoundException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    private function constructService(string $serviceName): void
    {
        // if the service already exists, do nothing
        if (isset($this->services[$serviceName])) {
            return;
        }

        // get the service definition that was created in locateService()
        // if there is no definition, assign null
        $config = $this->serviceDefinitions[$serviceName] ?? null;
        // if the config is null which happens when there is no service definition
        // it means that we're trying to construct a service from something that is not service
        // just throw an exception telling the user so
        if (is_null($config)) {
            throw new ServiceNotFoundException("The service {$serviceName} was not found");
        }
        // we need to get the service class as the service name does not always have to be a class
        $serviceClass = $config['class'];
        // check whether there are any configured parameters
        // if there are not we can just construct a new object and return
        if (!count($config['parameters'])) {
            // create a new object, this should work as we already checked that it doesn't have any parameters
            // in locateService()
            $object = new $serviceClass;
            // store it in constructed services
            $this->services[$serviceName] = $object;

            // return as the job is done for this service
            return;
        }

        // holder for constructed parameters
        $serviceParams = [];
        // traverse all the parameters and add them to the $serviceParams
        foreach ($config['parameters'] as $parameterName => $values) {
            // check for the type
            // if the type is service, we need to get the service from container
            // which recursively goes to this method if the service
            // wasn't constructed yet
            if ($values['type'] === 'service') {
                $serviceParams[] = $this->get($values['value']);
            // if the type is a simple value, just add it to $serviceParams
            } elseif ($values['type'] === 'preconfigured') {
                $serviceParams[] = $values['value'];
            // this shouldn't really happen unless you add a new type and forget
            // to handle it here
            } else {
                throw new \LogicException("Unknown type: '{$values['type']}'");
            }
        }

        // we create a new object giving it the parameters
        // these parameters should be in correct order in the $serviceParams as in locateServices()
        // we traversed them in the same order as they appear in constructor
        // if you don't know the ... syntax, it takes an array and puts all the values as individual parameters
        // to the function/method
        $service = new $serviceClass(...$serviceParams);
        // store the service in configured services
        $this->services[$serviceName] = $service;
    }

    /**
     * @return \Generator&\SplFileInfo[]
     */
    private function getPhpFiles()
    {
        $iterator =  new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                __DIR__
            )
        );

        foreach ($iterator as $file) {
            assert($file instanceof \SplFileInfo);
            if (!$file->isFile()) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }

            yield $file;
        }
    }
}
