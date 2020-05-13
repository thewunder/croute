<?php
namespace Croute;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ControllerFactoryTest extends TestCase
{
    public function testIndexController()
    {
        $factory = $this->getFactory();

        $request = Request::create('/');
        $controllerName = $factory->getControllerName($request);
        $controller = $factory->getController($request, $controllerName);
        $this->assertTrue($controller instanceof Fixtures\Controller\IndexController);
    }

    public function testNamedController()
    {
        $factory = $this->getFactory();

        $request = Request::create('/named/');
        $controllerName = $factory->getControllerName($request);
        $controller = $factory->getController($request, $controllerName);
        $this->assertTrue($controller instanceof Fixtures\Controller\NamedController);
    }

    public function testSanitization()
    {
        $factory = $this->getFactory();

        $request = Request::create('/nam..\..ed/');
        $controllerName = $factory->getControllerName($request);
        $this->assertEquals('Named', $controllerName);
    }

    public function testNamespacedControllers()
    {
        $factory = $this->getFactory();

        $request = Request::create('/myNamespace/');
        $controllerName = $factory->getControllerName($request);
        $controller = $factory->getController($request, $controllerName);
        $this->assertTrue($controller instanceof Fixtures\Controller\MyNamespace\IndexController);

        $request = Request::create('/myNamespace/named/');
        $controllerName = $factory->getControllerName($request);
        $controller = $factory->getController($request, $controllerName);
        $this->assertTrue($controller instanceof Fixtures\Controller\MyNamespace\NamedController);
    }

    public function testControllerNotFound()
    {
        $factory = $this->getFactory();

        $request = Request::create('/asdf/');
        $controllerName = $factory->getControllerName($request);
        $controller = $factory->getController($request, $controllerName);
        $this->assertNull($controller);
    }

    public function testGettersAndSetters()
    {
        $factory = $this->getFactory();

        $dependencies = ['asdf'];
        $factory->setDependencies($dependencies);
        $this->assertEquals($dependencies, $factory->getDependencies());

        $namespaces = ['namespace1', 'namespace2'];
        $factory->setNamespaces($namespaces);
        $this->assertEquals($namespaces, $factory->getNamespaces());
    }

    protected function getFactory()
    {
        return new ControllerFactory(['Croute\\Fixtures\\Controller'], []);
    }
}
