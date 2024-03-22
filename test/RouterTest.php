<?php
namespace Croute\Test;

use Croute\Attributes\SecureHandler;
use Croute\ControllerFactory;
use Croute\ErrorHandlerInterface;
use Croute\Event\BeforeActionEvent;
use Croute\Event\RequestEvent;
use Croute\Router;
use Croute\Test\Fixtures\Controller\AttributeTestController;
use Croute\Test\Fixtures\Controller\RouterTestController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class RouterTest extends TestCase
{
    private ControllerFactory|MockObject $factory;
    private Router $router;

    public function setUp(): void
    {
        $this->factory = $this->getMockBuilder(\Croute\ControllerFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getController'])
            ->getMock();

        $this->factory->method('getController')
            ->willReturn(new RouterTestController());

        $this->router = new Router($this->factory, new EventDispatcher());
    }

    public function testCreateWithContainer(): void
    {
        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $router = Router::create(new EventDispatcher(), ['Croute'], $container, []);
        $this->assertTrue($router->getControllerFactory() instanceof ControllerFactory);
    }

    public function testRoute(): void
    {
        $response = $this->router->route(Request::create('/'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testControllerNotFound(): void
    {
        $this->factory->method('getController')
            ->willReturn(null);

        $response = $this->router->route(Request::create('/asdf'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testActionNotFound(): void
    {
        $response = $this->router->route(Request::create('/asdf'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testActionAttribute(): void
    {
        $request = Request::create('/secure');
        $this->router->addAttributeHandler(new SecureHandler());
        $response = $this->router->route($request);
        $this->assertEquals(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testControllerAttribute(): void
    {
        $this->factory = $this->getMockBuilder(\Croute\ControllerFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getController'])
            ->getMock();

        $this->factory->method('getController')
            ->willReturn(new AttributeTestController());

        $this->router = new Router($this->factory, new EventDispatcher());
        $this->router->addAttributeHandler(new SecureHandler());

        $response = $this->router->route(Request::create('/attributeTest/insecure'));

        $this->assertEquals(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testReturn(): void
    {
        $response = $this->router->route(Request::create('/return'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello', $response->getContent());
    }

    public function testParams(): void
    {
        $response = $this->router->route(Request::create('/params?required=Hello'));
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent());
        $this->assertEquals('Hello', $data->required);
        $this->assertEquals('defaultValue', $data->optional);
    }

    public function testEvents(): void
    {
        /** @var EventDispatcher|MockObject $mockDispatcher */
        $mockDispatcher = $this->getMockBuilder(\Symfony\Component\EventDispatcher\EventDispatcher::class)
            ->onlyMethods(['dispatch'])
            ->getMock();

        $request = Request::create('/');

        // 12 = 1 request, 2 controller loaded, 3 before action, 3 after after, 1 before sent, 3 response sent
        $mockDispatcher->expects($this->exactly(13))->method('dispatch');

        $router = new Router($this->factory, $mockDispatcher);
        $router->route($request);
    }

    public function testResponseFromRequestListener(): void
    {
        $this->factory->expects($this->never())
            ->method('getController');

        $dispatcher = new EventDispatcher();
        $router = new Router($this->factory, $dispatcher);

        $dispatcher->addListener('router.request', function (RequestEvent $event) {
            $event->setResponse(new Response('I\'m a teapot', 418));
        });

        $response = $router->route(Request::create('/'));
        $this->assertEquals(418, $response->getStatusCode());
        $this->assertEquals('I\'m a teapot', $response->getContent());
    }

    public function testResponseFromBeforeActionListener(): void
    {
        $dispatcher = new EventDispatcher();
        $router = new Router($this->factory, $dispatcher);

        $dispatcher->addListener('router.before_action', function (BeforeActionEvent $event) {
            $event->setResponse(new Response('I\'m a teapot', 418));
        });

        $response = $router->route(Request::create('/'));
        $this->assertEquals(418, $response->getStatusCode());
        $this->assertEquals('I\'m a teapot', $response->getContent());
    }

    public function testException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->router->route(Request::create('/exception'));
    }

    public function testExceptionInListener(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->factory->method('getController')
            ->willReturn(new RouterTestController());
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('router.controller_loaded', function () {
            throw new \RuntimeException('Explode');
        });
        $router = new Router($this->factory, $dispatcher);

        $router->route(Request::create('/'));
    }

    public function testErrorHandlerException(): void
    {
        /** @var ErrorHandlerInterface|MockObject $mockErrorHandler */
        $mockErrorHandler = $this->getMockBuilder(\Croute\ErrorHandlerInterface::class)
            ->onlyMethods(['displayErrorPage', 'handleException'])
            ->getMock();

        $mockErrorHandler->expects($this->once())->method('handleException')
            ->with($this->isInstanceOf('RuntimeException'))
            ->willReturn(new Response('', 500));

        $this->router->setErrorHandler($mockErrorHandler);
        $response = $this->router->route(Request::create('/exception'));
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testAddRoute(): void
    {
        $this->router->addRoute('/custom/{input}/static', 'GET', 'RouterTest', 'echo');
        $response = $this->router->route(Request::create('/custom/xyz/static'));
        $this->assertEquals('xyz', $response->getContent());
    }

    public function testAddRoutes(): void
    {
        $this->router->addRoutes([['/custom/{input}/static', 'GET', 'RouterTest', 'echo'],
            ['/custom/{input}/asdf', 'GET', 'RouterTest', 'return']]);
        $response = $this->router->route(Request::create('/custom/xyz/static'));
        $this->assertEquals('xyz', $response->getContent());
        $response = $this->router->route(Request::create('/custom/xyz/asdf'));
        $this->assertEquals('Hello', $response->getContent());
    }

    public function testAddRouteNoAction(): void
    {
        $this->router->addRoute('/custom/{input}/return', 'GET', 'RouterTest');
        $response = $this->router->route(Request::create('/custom/xyz/return'));
        $this->assertEquals('Hello', $response->getContent());
    }

    public function testAddRouteInvalidMethod(): void
    {
        $this->router->addRoute('/custom/{input}', 'POST', 'RouterTest', 'echo');
        $response = $this->router->route(Request::create('/custom/xyz'));
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testAddCustomRoute(): void
    {
        $this->router->addCustomRoute('custom_route', new Route('/custom/{input}', ['_controller'=>'RouterTest::echo']));
        $response = $this->router->route(Request::create('/custom/xyz'));
        $this->assertEquals('xyz', $response->getContent());
    }

    public function testAddCustomRouteMissingController(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->router->addCustomRoute('custom_route', new Route('/custom/{input}'));
        $this->router->route(Request::create('/custom/xyz'));
    }

    public function testErrorHandlerExceptionInListener(): void
    {
        /** @var ErrorHandlerInterface|MockObject $mockErrorHandler */
        $mockErrorHandler = $this->getMockBuilder(\Croute\ErrorHandlerInterface::class)
            ->onlyMethods(['displayErrorPage', 'handleException'])
            ->getMock();

        $mockErrorHandler->expects($this->once())->method('handleException')
            ->with($this->isInstanceOf('RuntimeException'))
            ->willReturn(new Response('', 500));

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('router.controller_loaded', function () {
            throw new \RuntimeException('Explode');
        });

        $router = new Router($this->factory, $dispatcher);

        $router->setErrorHandler($mockErrorHandler);
        $response = $router->route(Request::create('/'));
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testErrorHandlerErrorPage(): void
    {
        /** @var ErrorHandlerInterface|MockObject $mockErrorHandler */
        $mockErrorHandler = $this->getMockBuilder(\Croute\ErrorHandlerInterface::class)
            ->onlyMethods(['displayErrorPage', 'handleException'])
            ->getMock();

        $mockErrorHandler->expects($this->once())->method('displayErrorPage')
            ->willReturn(new Response('', 404));

        $this->router->setErrorHandler($mockErrorHandler);
        $response = $this->router->route(Request::create('/asdf'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMissingParams(): void
    {
        $response = $this->router->route(Request::create('/params'));
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testInaccessibleAction(): void
    {
        $response = $this->router->route(Request::create('/protected'));
        $this->assertEquals(500, $response->getStatusCode());
    }
}
