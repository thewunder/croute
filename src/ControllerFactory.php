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
     * @return ControllerInterface
     */
    public function getController(Request $request)
    {
        $controllerName = $this->getControllerName($request);

        foreach($this->namespaces as $namespace)
        {
            $controllerClass = $namespace . '\\' . $controllerName . 'Controller';

            $controller = null;
            if(class_exists($controllerClass)) {
                $controller = $this->createController($controllerClass);
            } else {
                //could be index controller of namespace
                $controllerName = $controllerName.'\\Index';
                $controllerClass = $namespace . '\\' . $controllerName . 'Controller';
                if(class_exists($controllerClass)) {
                    $controller = $this->createController($controllerClass);
                }
            }

            if($controller instanceof ControllerInterface) {
                $request->attributes->set('controller', $controllerName);
                $controller->setRequest($request);
                return $controller;
            }
        }

        return null;
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

    /**
     * @param Request $request
     * @return string
     */
    protected function getControllerName(Request $request)
    {
        $path = $request->getPathInfo();
        if(strrpos($path, '/')) {
            $path = $request->getBaseUrl() . $path;
            $controllerName = substr($path, 1, strrpos($path, '/') - 1); //chop off leading /
            $controllerName = preg_replace(array('#[^a-z0-9/]#i','#/#'), array('', '\\'), $controllerName); //sanitize and flip / to \
            $controllerName = preg_replace_callback(array('#^[a-z]#','#\\\\[a-z]#'), //normalize capitalization
                function($matches){
                    return strtoupper($matches[0]);
                }, $controllerName);
            return $controllerName;
        } else {
            return 'Index';
        }
    }
}