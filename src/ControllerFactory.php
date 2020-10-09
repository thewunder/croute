<?php
namespace Croute;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ControllerFactory implements ControllerFactoryInterface
{
    /** @var array */
    protected $namespaces;

    /** @var array */
    protected $dependencies;

    /**  @var ContainerInterface|null */
    private $container;

    /**
     * @param array $namespaces Array of namespaces containing to search for controllers
     * @param array|null $dependencies Array of dependencies to pass as constructor arguments to controllers
     * @param ContainerInterface|null $container PSR-11 Container to use to instantiate controllers
     */
    public function __construct(array $namespaces, array $dependencies = [], ?ContainerInterface $container = null)
    {
        $this->namespaces = $namespaces;
        $this->dependencies = $dependencies;
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getControllerName(Request $request): string
    {
        $path = $request->getPathInfo();
        if (strrpos($path, '/')) {
            $path = $request->getBaseUrl() . $path;
            $controllerName = substr($path, 1, strrpos($path, '/') - 1); //chop off leading /
            $controllerName = preg_replace(['#[^a-z0-9/]#i','#/#'], ['', '\\'], $controllerName); //sanitize and flip / to \
            $controllerName = preg_replace_callback(
                ['#^[a-z]#',
                '#\\\\[a-z]#'], //normalize capitalization
                function ($matches) {
                    return strtoupper($matches[0]);
                },
                $controllerName
            );
            return $controllerName;
        } else {
            return 'Index';
        }
    }

    /**
     * @param Request $request
     * @param string $controllerName
     * @return ControllerInterface
     */
    public function getController(Request $request, string $controllerName): ?ControllerInterface
    {
        foreach ($this->namespaces as $namespace) {
            $controllerClass = $namespace . '\\' . $controllerName . 'Controller';

            $controller = null;
            if (class_exists($controllerClass)) {
                $controller = $this->createController($controllerClass);
            } else {
                //could be index controller of namespace
                $controllerName = $controllerName.'\\Index';
                $controllerClass = $namespace . '\\' . $controllerName . 'Controller';
                if (class_exists($controllerClass)) {
                    $controller = $this->createController($controllerClass);
                }
            }

            if ($controller instanceof ControllerInterface) {
                return $controller;
            }
        }

        return null;
    }

    /**
     * @param string[] $namespaces
     */
    public function setNamespaces(array $namespaces): void
    {
        $this->namespaces = $namespaces;
    }

    /**
     * @param array $dependencies
     */
    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @return string[]
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @param string $controllerClass
     * @return ControllerInterface
     */
    protected function createController(string $controllerClass): ?ControllerInterface
    {
        if ($this->container && $this->container->has($controllerClass)) {
            return $this->container->get($controllerClass);
        }

        $class = new \ReflectionClass($controllerClass);
        $controller = $class->newInstanceArgs($this->dependencies);
        if ($controller instanceof ControllerInterface) {
            return $controller;
        }
        return null;
    }
}
