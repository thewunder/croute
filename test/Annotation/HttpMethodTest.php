<?php
namespace Croute\Test\Annotation;

use Croute\Annotation\HttpMethod;
use Croute\Event\BeforeActionEvent;
use Croute\Event\ControllerLoadedEvent;
use Croute\Test\Fixtures\Controller\HttpMethodTestController;
use Minime\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class HttpMethodTest extends TestCase
{
    public function testClassAnnotation()
    {
        $handler = $this->getHandler();

        $request = Request::create('/');
        $event = new ControllerLoadedEvent($request, new HttpMethodTestController());
        $handler->handleControllerAnnotations($event);
        $this->assertEquals(405, $event->getResponse()->getStatusCode());

        $request = Request::create('/', 'POST');
        $event = new ControllerLoadedEvent($request, new HttpMethodTestController());
        $handler->handleControllerAnnotations($event);
        $this->assertNull($event->getResponse());
    }

    public function testSingleAnnotation()
    {
        $handler = $this->getHandler();

        $request = Request::create('/');
        $controller = new HttpMethodTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'singleAnnotationAction'));
        $handler->handleActionAnnotations($event);
        $this->assertEquals(405, $event->getResponse()->getStatusCode());

        $request = Request::create('/', 'DELETE');
        $controller = new HttpMethodTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'singleAnnotationAction'));
        $handler->handleActionAnnotations($event);
        $this->assertNull($event->getResponse());
    }

    public function testMultipleAnnotations()
    {
        $handler = $this->getHandler();

        $request = Request::create('/');
        $controller = new HttpMethodTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'multipleAnnotationAction'));
        $handler->handleActionAnnotations($event);
        $this->assertEquals(405, $event->getResponse()->getStatusCode());

        $request = Request::create('/', 'PUT');
        $controller = new HttpMethodTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'multipleAnnotationAction'));
        $handler->handleActionAnnotations($event);
        $this->assertNull($event->getResponse());
    }

    public function testNoAnnotation()
    {
        $handler = $this->getHandler();

        $request = Request::create('/');
        $controller = new HttpMethodTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'noAnnotationAction'));
        $handler->handleActionAnnotations($event);
        $this->assertNull($event->getResponse());
    }

    public function testNoHttpMethodSpecified()
    {
        $this->expectException(\InvalidArgumentException::class);
        $handler = $this->getHandler();

        $request = Request::create('/');
        $controller = new HttpMethodTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'noHttpMethodSpecifiedAction'));
        $handler->handleActionAnnotations($event);
    }

    /**
     * @return HttpMethod
     */
    protected function getHandler()
    {
        return new HttpMethod(Reader::createFromDefaults());
    }
}
