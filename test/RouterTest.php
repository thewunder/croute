<?php
namespace Croute;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testRoute()
    {
        $response = $this->getRouter()->route(Request::create('/'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testControllerNotFound()
    {
        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->setMethods(array('getController'))
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
            ->setMethods(array('handleControllerAnnotations', 'handleActionAnnotations'))
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
        } catch(\InvalidArgumentException $e) {
            //expected
        }

        $router->route(Request::create('/'));
    }

    public function testRemoveAnnotationHandler()
    {
        $mock = $this->getMockBuilder('Croute\\Annotation\\HttpMethod')
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();

        $mock->expects($this->exactly(2))->method('getName')->willReturn('httpMethod');

        $router = $this->getRouter();
        $router->addAnnotationHandler($mock);
        $router->removeAnnotationHandler('httpMethod');

        try {
            $router->removeAnnotationHandler('asdf');
            $this->fail('Should have thrown illegal argument exception');
        } catch(\InvalidArgumentException $e) {
            //expected
        }

        $router->route(Request::create('/'));
    }

    public function testReturn()
    {
        $response = $this->getRouter()->route(Request::create('/return'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello', $response->getContent());
    }

    public function testParams()
    {
        $mockController = $this->getMockBuilder('Croute\\RouterTestController')
            ->setMethods(array('paramsAction'))
            ->getMock();

        $mockController->expects($this->once())->method('paramsAction')
            ->with($this->equalTo('Hello'), $this->equalTo('World'));

        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->setMethods(array('getController'))
            ->getMock();
        $factory->expects($this->once())
            ->method('getController')
            ->willReturn($mockController);

        $router = new Router($factory, new EventDispatcher());

        $response = $router->route(Request::create('/params?required=Hello&optional=World'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testEvents()
    {
        $mockDispatcher = $this->getMockBuilder('Symfony\\Component\\EventDispatcher\\EventDispatcher')
            ->setMethods(array('dispatch'))
            ->getMock();

        $request = Request::create('/');
        //these would ordinarily be set by the controller factory
        $request->attributes->set('controller', 'Index');
        $request->attributes->set('action', 'index');

        $mockDispatcher->expects($this->exactly(15))->method('dispatch');

        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->setMethods(array('getController'))
            ->getMock();
        $factory->expects($this->any())
            ->method('getController')
            ->willReturn(new RouterTestController());

        $router = new Router($factory, $mockDispatcher);
        $router->route($request);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Explode
     */
    public function testException()
    {
        $this->getRouter()->route(Request::create('/exception'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Explode
     */
    public function testExceptionInListener()
    {
        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->setMethods(array('getController'))
            ->getMock();
        $factory->expects($this->any())
            ->method('getController')
            ->with($this->isInstanceOf('Symfony\\Component\\HttpFoundation\\Request'))
            ->willReturn(new RouterTestController());
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('router.controller_loaded', function() {
            throw new \RuntimeException('Explode');
        });
        $router = new Router($factory, $dispatcher);

        $router->route(Request::create('/'));
    }

    public function testErrorHandlerException()
    {
        $mockErrorHandler = $this->getMockBuilder('Croute\\ErrorHandlerInterface')
            ->setMethods(array('displayErrorPage', 'handleException'))
            ->getMock();

        $mockErrorHandler->expects($this->once())->method('handleException')
            ->with($this->isInstanceOf('RuntimeException'))
            ->willReturn(new Response('', 500));

        $router = $this->getRouter();
        $router->setErrorHandler($mockErrorHandler);
        $response = $router->route(Request::create('/exception'));
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testErrorHandlerExceptionInListener()
    {
        $mockErrorHandler = $this->getMockBuilder('Croute\\ErrorHandlerInterface')
            ->setMethods(array('displayErrorPage', 'handleException'))
            ->getMock();

        $mockErrorHandler->expects($this->once())->method('handleException')
            ->with($this->isInstanceOf('RuntimeException'))
            ->willReturn(new Response('', 500));

        $factory = $this->getMockBuilder('Croute\\ControllerFactory')
            ->disableOriginalConstructor()
            ->setMethods(array('getController'))
            ->getMock();
        $factory->expects($this->once())
            ->method('getController')
            ->with($this->isInstanceOf('Symfony\\Component\\HttpFoundation\\Request'))
            ->willReturn(new RouterTestController());
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('router.controller_loaded', function() {
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
            ->setMethods(array('displayErrorPage', 'handleException'))
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
            ->setMethods(array('getController'))
            ->getMock();
        $factory->expects($this->once())
            ->method('getController')
            ->with($this->isInstanceOf('Symfony\\Component\\HttpFoundation\\Request'))
            ->willReturn(new RouterTestController());
        return new Router($factory, new EventDispatcher());
    }
}

class RouterTestController extends Controller
{
    public function indexAction()
    {
    }

    public function paramsAction($required, $optional = null)
    {
    }

    public function returnAction()
    {
        return new Response('Hello');
    }

    public function exceptionAction()
    {
        throw new \RuntimeException('Explode');
    }

    protected function protectedAction()
    {
    }
}