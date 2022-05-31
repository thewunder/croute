<?php
namespace Croute\Test;

use Croute\ControllerFactory;
use Croute\Test\Fixtures\Controller;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ControllerFactoryTest extends TestCase
{
    public function testIndexController()
    {
        $factory = $this->getFactory();

        $request = Request::create('/');
        $controllerName = $factory->getControllerName($request);
        $controller = $factory->getController($request, $controllerName);
        $this->assertInstanceOf(Controller\IndexController::class, $controller);
    }

    public function testNamedController()
    {
        $factory = $this->getFactory();

        $request = Request::create('/named/');
        $controllerName = $factory->getControllerName($request);
        $controller = $factory->getController($request, $controllerName);
        $this->assertInstanceOf(Controller\NamedController::class, $controller);
    }

    public function testControllerWithContainer()
    {
        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->expects($this->once())->method('has')->with(Controller\NamedController::class)->willReturn(true);
        $controller = new Controller\NamedController();
        $container->expects($this->once())->method('get')->with(Controller\NamedController::class)->willReturn($controller);
        $factory = $this->getFactory($container);
        $request = Request::create('/named/');
        $controllerName = $factory->getControllerName($request);
        $fromFactory = $factory->getController($request, $controllerName);
        $this->assertEquals($controller, $fromFactory);
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
        $this->assertInstanceOf(Controller\MyNamespace\IndexController::class, $controller);

        $request = Request::create('/myNamespace/named/');
        $controllerName = $factory->getControllerName($request);
        $controller = $factory->getController($request, $controllerName);
        $this->assertInstanceOf(Controller\MyNamespace\NamedController::class, $controller);
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

    protected function getFactory(?ContainerInterface $container = null): ControllerFactory
    {
        return new ControllerFactory(['Croute\\Test\\Fixtures\\Controller'], [], $container);
    }
}
