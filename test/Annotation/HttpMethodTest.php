<?php
namespace Croute\Annotation;

use Croute\Controller;
use Croute\Event\BeforeActionEvent;
use Croute\Event\ControllerLoadedEvent;
use Minime\Annotations\Reader;
use Symfony\Component\HttpFoundation\Request;

class HttpMethodTest extends \PHPUnit_Framework_TestCase
{
    public function testClassAnnotation()
    {
        $handler = $this->getHandler();

        $request = Request::create('/');
        $event = new ControllerLoadedEvent($request, new HttpMethodTestController());
        $handler->handleControllerAnnotations($event);
        $this->assertEquals(400, $event->getResponse()->getStatusCode());

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
        $this->assertEquals(400, $event->getResponse()->getStatusCode());

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
        $this->assertEquals(400, $event->getResponse()->getStatusCode());

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
        $handler = $this->getHandler();

        $request = Request::create('/');
        $controller = new HttpMethodTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'noHttpMethodSpecifiedAction'));
        try {
            $handler->handleActionAnnotations($event);
            $this->fail('Invalid argument exception not thrown');
        } catch (\InvalidArgumentException $e) {
            //expected
        }

    }

    /**
     * @return HttpMethod
     */
    protected function getHandler()
    {
        return new HttpMethod(Reader::createFromDefaults());
    }
}

/**
 * @httpMethod post
 */
class HttpMethodTestController extends Controller
{

    public function noAnnotationAction()
    {
    }

    /**
     * @httpMethod DELETE
     */
    public function singleAnnotationAction()
    {
    }

    /**
     * @httpMethod ["PUT", "POST"]
     */
    public function multipleAnnotationAction()
    {
    }

    /**
     * @httpMethod
     */
    public function noHttpMethodSpecifiedAction()
    {
    }
}
