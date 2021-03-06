<?php
namespace Croute\Test;

use Croute\ControllerFactory;
use Croute\Event\BeforeActionEvent;
use Croute\Event\RequestEvent;
use Croute\Router;
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
    public function testCreate()
    {
        $router = Router::create(new EventDispatcher(), ['Croute']);
        $this->assertTrue($router->getControllerFactory() instanceof ControllerFactory);
    }

    public function testCreateWithContainer()
    {
        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $router = Router::create(new EventDispatcher(), ['Croute'], [], $container);
        $this->assertTrue($router->getControllerFactory() instanceof ControllerFactory);
    }

    public function testRoute()
    {
        $response = $this->getRouter()->route(Request::create('/'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testControllerNotFound()
    {
        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getController'))
            ->getMock();
        $factory->expects($this->once())
            ->method('getController')
            ->willReturn(null);

        $router = new Router($factory, new EventDispatcher());

        $response = $router->route(Request::create('/asdf'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testActionNotFound()
    {
        $response = $this->getRouter()->route(Request::create('/asdf'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testAnnotationHandler()
    {
        $mock = $this->getMockBuilder('Croute\\Annotation\\HttpMethod')
            ->disableOriginalConstructor()
            ->onlyMethods(array('handleControllerAnnotations', 'handleActionAnnotations'))
            ->getMock();

        $mock->expects($this->once())->method('handleControllerAnnotations')
            ->with($this->isInstanceOf('Croute\\Event\\ControllerLoadedEvent'));

        $mock->expects($this->once())->method('handleActionAnnotations')
            ->with($this->isInstanceOf('Croute\\Event\\BeforeActionEvent'));

        $router = $this->getRouter();
        $router->addAnnotationHandler($mock);

        try {
            $router->addAnnotationHandler($mock);
            $this->fail('Should have thrown illegal argument exception');
        } catch (\InvalidArgumentException $e) {
            //expected
        }

        $router->route(Request::create('/'));
    }

    public function testRemoveAnnotationHandler()
    {
        $mock = $this->getMockBuilder('Croute\\Annotation\\HttpMethod')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getName'))
            ->getMock();

        $mock->expects($this->exactly(2))->method('getName')->willReturn('httpMethod');

        $router = Router::create(new EventDispatcher(), []);
        $router->addAnnotationHandler($mock);
        $router->removeAnnotationHandler('httpMethod');
    }

    public function testRemoveMissingAnnotationHandler()
    {
        $this->expectException(\InvalidArgumentException::class);
        $router = Router::create(new EventDispatcher(), []);
        $router->removeAnnotationHandler('httpMethod');
    }

    public function testReturn()
    {
        $response = $this->getRouter()->route(Request::create('/return'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello', $response->getContent());
    }

    public function testParams()
    {
        $mockController = $this->getMockBuilder('Croute\\Test\\Fixtures\\Controller\\RouterTestController')
            ->onlyMethods(array('paramsAction'))
            ->getMock();

        $mockController->expects($this->once())->method('paramsAction')
            ->with($this->equalTo('Hello'), $this->equalTo('defaultValue'));

        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getController'))
            ->getMock();
        $factory->expects($this->once())
            ->method('getController')
            ->willReturn($mockController);

        $router = new Router($factory, new EventDispatcher());

        $response = $router->route(Request::create('/params?required=Hello'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testEvents()
    {
        $mockDispatcher = $this->getMockBuilder('Symfony\\Component\\EventDispatcher\\EventDispatcher')
            ->onlyMethods(array('dispatch'))
            ->getMock();

        $request = Request::create('/');

        // 12 = 1 request, 2 controller loaded, 3 before action, 3 after after, 1 before sent, 3 response sent
        $mockDispatcher->expects($this->exactly(13))->method('dispatch');

        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getController'))
            ->getMock();
        $factory->expects($this->any())
            ->method('getController')
            ->willReturn(new Fixtures\Controller\RouterTestController());

        $router = new Router($factory, $mockDispatcher);
        $router->route($request);
    }

    public function testResponseFromRequestListener()
    {
        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getController'))
            ->getMock();
        $factory->expects($this->never())
            ->method('getController');

        $dispatcher = new EventDispatcher();
        $router = new Router($factory, $dispatcher);

        $dispatcher->addListener('router.request', function (RequestEvent $event) {
            $event->setResponse(new Response('I\'m a teapot', 418));
        });

        $response = $router->route(Request::create('/'));
        $this->assertEquals(418, $response->getStatusCode());
        $this->assertEquals('I\'m a teapot', $response->getContent());
    }

    public function testResponseFromBeforeActionListener()
    {
        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getController'))
            ->getMock();
        $factory->expects($this->once())
            ->method('getController')
            ->with($this->isInstanceOf('Symfony\\Component\\HttpFoundation\\Request'))
            ->willReturn(new RouterTestController());

        $dispatcher = new EventDispatcher();
        $router = new Router($factory, $dispatcher);

        $dispatcher->addListener('router.before_action', function (BeforeActionEvent $event) {
            $event->setResponse(new Response('I\'m a teapot', 418));
        });

        $response = $router->route(Request::create('/'));
        $this->assertEquals(418, $response->getStatusCode());
        $this->assertEquals('I\'m a teapot', $response->getContent());
    }

    public function testException()
    {
        $this->expectException(\RuntimeException::class);
        $this->getRouter()->route(Request::create('/exception'));
    }

    public function testExceptionInListener()
    {
        $this->expectException(\RuntimeException::class);
        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getController'))
            ->getMock();
        $factory->expects($this->any())
            ->method('getController')
            ->with($this->isInstanceOf('Symfony\\Component\\HttpFoundation\\Request'))
            ->willReturn(new RouterTestController());
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('router.controller_loaded', function () {
            throw new \RuntimeException('Explode');
        });
        $router = new Router($factory, $dispatcher);

        $router->route(Request::create('/'));
    }

    public function testErrorHandlerException()
    {
        $mockErrorHandler = $this->getMockBuilder('Croute\\ErrorHandlerInterface')
            ->onlyMethods(array('displayErrorPage', 'handleException'))
            ->getMock();

        $mockErrorHandler->expects($this->once())->method('handleException')
            ->with($this->isInstanceOf('RuntimeException'))
            ->willReturn(new Response('', 500));

        $router = $this->getRouter();
        $router->setErrorHandler($mockErrorHandler);
        $response = $router->route(Request::create('/exception'));
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testAddRoute()
    {
        $router = Router::create(new EventDispatcher(), ['Croute\\Test\\Fixtures\\Controller']);
        $router->addRoute('/custom/{input}/static', 'GET', 'RouterTest', 'echo');
        $response = $router->route(Request::create('/custom/xyz/static'));
        $this->assertEquals('xyz', $response->getContent());
    }

    public function testAddRoutes()
    {
        $router = Router::create(new EventDispatcher(), ['Croute\\Test\\Fixtures\\Controller']);
        $router->addRoutes([['/custom/{input}/static', 'GET', 'RouterTest', 'echo'],
            ['/custom/{input}/asdf', 'GET', 'RouterTest', 'return']]);
        $response = $router->route(Request::create('/custom/xyz/static'));
        $this->assertEquals('xyz', $response->getContent());
        $response = $router->route(Request::create('/custom/xyz/asdf'));
        $this->assertEquals('Hello', $response->getContent());
    }

    public function testAddRouteNoAction()
    {
        $router = Router::create(new EventDispatcher(), ['Croute\\Test\\Fixtures\\Controller']);
        $router->addRoute('/custom/{input}/return', 'GET', 'RouterTest');
        $response = $router->route(Request::create('/custom/xyz/return'));
        $this->assertEquals('Hello', $response->getContent());
    }

    public function testAddRouteInvalidMethod()
    {
        $router = Router::create(new EventDispatcher(), ['Croute\\Test\\Fixtures\\Controller']);
        $router->addRoute('/custom/{input}', 'POST', 'RouterTest', 'echo');
        $response = $router->route(Request::create('/custom/xyz'));
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testAddCustomRoute()
    {
        $router = Router::create(new EventDispatcher(), ['Croute\\Test\\Fixtures\\Controller']);
        $router->addCustomRoute('custom_route', new Route('/custom/{input}', ['_controller'=>'RouterTest::echo']));
        $response = $router->route(Request::create('/custom/xyz'));
        $this->assertEquals('xyz', $response->getContent());
    }

    public function testAddCustomRouteMissingController()
    {
        $this->expectException(\InvalidArgumentException::class);
        $router = Router::create(new EventDispatcher(), ['Croute\\Test\\Fixtures\\Controller']);
        $router->addCustomRoute('custom_route', new Route('/custom/{input}'));
        $router->route(Request::create('/custom/xyz'));
    }

    public function testErrorHandlerExceptionInListener()
    {
        $mockErrorHandler = $this->getMockBuilder('Croute\\ErrorHandlerInterface')
            ->onlyMethods(array('displayErrorPage', 'handleException'))
            ->getMock();

        $mockErrorHandler->expects($this->once())->method('handleException')
            ->with($this->isInstanceOf('RuntimeException'))
            ->willReturn(new Response('', 500));

        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getController'))
            ->getMock();
        $factory->expects($this->once())
            ->method('getController')
            ->with($this->isInstanceOf('Symfony\\Component\\HttpFoundation\\Request'))
            ->willReturn(new RouterTestController());
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('router.controller_loaded', function () {
            throw new \RuntimeException('Explode');
        });
        $router = new Router($factory, $dispatcher);

        $router->setErrorHandler($mockErrorHandler);
        $response = $router->route(Request::create('/'));
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testErrorHandlerErrorPage()
    {
        $mockErrorHandler = $this->getMockBuilder('Croute\\ErrorHandlerInterface')
            ->onlyMethods(array('displayErrorPage', 'handleException'))
            ->getMock();

        $mockErrorHandler->expects($this->once())->method('displayErrorPage')
            ->willReturn(new Response('', 404));

        $router = $this->getRouter();
        $router->setErrorHandler($mockErrorHandler);
        $response = $router->route(Request::create('/asdf'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMissingParams()
    {
        $response = $this->getRouter()->route(Request::create('/params'));
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testInaccessibleAction()
    {
        $response = $this->getRouter()->route(Request::create('/protected'));
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @return Router
     */
    protected function getRouter()
    {
        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(['getController'])
            ->getMock();
        $factory->expects($this->once())
            ->method('getController')
            ->with($this->isInstanceOf('Symfony\\Component\\HttpFoundation\\Request'))
            ->willReturn(new RouterTestController());
        return new Router($factory, new EventDispatcher());
    }
}
