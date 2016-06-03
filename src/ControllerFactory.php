<?php
namespace Croute;

use Symfony\Component\HttpFoundation\Request;

class ControllerFactory implements ControllerFactoryInterface
{
    /** @var array */
    protected $namespaces;

    /** @var array */
    protected $dependencies;

    /**
     * @param array $namespaces Array of namespaces containing to search for controllers
     * @param array $dependencies Array of dependencies to pass as constructor arguments to controllers
     */
    public function __construct(array $namespaces, array $dependencies)
    {
        $this->namespaces = $namespaces;
        $this->dependencies = $dependencies;
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getControllerName(Request $request)
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
    public function getController(Request $request, $controllerName)
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
     * @param array $namespaces
     */
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;
    }

    /**
     * @param array $dependencies
     */
    public function setDependencies(array $dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @return array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @param $controllerClass
     * @return object
     */
    protected function createController($controllerClass)
    {
        $class = new \ReflectionClass($controllerClass);
        $instance = $class->newInstanceArgs($this->dependencies);
        return $instance;
    }
}
