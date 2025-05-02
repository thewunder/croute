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
    private ControllerFactory $factory;
    private ContainerInterface|MockObject $container;
    
    public function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $this->factory = new ControllerFactory(['Croute\\Test\\Fixtures\\Controller'], $this->container, []);
    }

    public function testIndexController(): void
    {
        $request = Request::create('/');
        $controllerName = $this->factory->getControllerName($request);
        $controller = $this->factory->getController($request, $controllerName);
        $this->assertInstanceOf(Controller\IndexController::class, $controller);
    }

    public function testNamedController(): void
    {
        $request = Request::create('/named/');
        $controllerName = $this->factory->getControllerName($request);
        $controller = $this->factory->getController($request, $controllerName);
        $this->assertInstanceOf(Controller\NamedController::class, $controller);
    }

    public function testControllerWithContainer(): void
    {
        $this->container->expects($this->once())->method('has')->with(Controller\NamedController::class)->willReturn(true);
        $controller = new Controller\NamedController();
        $this->container->expects($this->once())->method('get')->with(Controller\NamedController::class)->willReturn($controller);
        $request = Request::create('/named/');
        $controllerName = $this->factory->getControllerName($request);
        $fromFactory = $this->factory->getController($request, $controllerName);
        $this->assertEquals($controller, $fromFactory);
    }

    public function testSanitization(): void
    {
        $request = Request::create('/nam....ed/');
        $controllerName = $this->factory->getControllerName($request);
        $this->assertEquals('Named', $controllerName);
    }

    public function testNamespacedControllers(): void
    {
        $request = Request::create('/myNamespace/');
        $controllerName = $this->factory->getControllerName($request);
        $controller = $this->factory->getController($request, $controllerName);
        $this->assertInstanceOf(Controller\MyNamespace\IndexController::class, $controller);

        $request = Request::create('/myNamespace/named/');
        $controllerName = $this->factory->getControllerName($request);
        $controller = $this->factory->getController($request, $controllerName);
        $this->assertInstanceOf(Controller\MyNamespace\NamedController::class, $controller);
    }

    public function testControllerNotFound(): void
    {
        $request = Request::create('/asdf/');
        $controllerName = $this->factory->getControllerName($request);
        $controller = $this->factory->getController($request, $controllerName);
        $this->assertNull($controller);
    }
}
