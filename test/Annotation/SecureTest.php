<?php
namespace Croute\Test\Annotation;

use Croute\Annotation\Secure;
use Croute\Event\BeforeActionEvent;
use Croute\Event\ControllerLoadedEvent;
use Croute\Test\Fixtures\Controller\SecureTestController;
use Minime\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SecureTest extends TestCase
{
    public function testClassAnnotation()
    {
        $handler = $this->getHandler();

        $request = Request::create('/');
        $event = new ControllerLoadedEvent($request, new SecureTestController());
        $handler->handleControllerAnnotations($event);
        $this->assertEquals(301, $event->getResponse()->getStatusCode());
        $this->assertEquals('https://localhost/', $event->getResponse()->headers->get('Location'));
    }

    public function testAnnotation()
    {
        $handler = $this->getHandler();

        $request = Request::create('/');
        $controller = new SecureTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'secureAction'));
        $handler->handleActionAnnotations($event);
        $this->assertEquals(301, $event->getResponse()->getStatusCode());
        $this->assertEquals('https://localhost/', $event->getResponse()->headers->get('Location'));

        $request = Request::create('/');
        $request->server->set('HTTPS', 'https');
        $controller = new SecureTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'secureAction'));
        $handler->handleActionAnnotations($event);
        $this->assertNull($event->getResponse());
    }

    public function testNoAnnotation()
    {
        $handler = $this->getHandler();

        $request = Request::create('/');
        $controller = new SecureTestController();
        $event = new BeforeActionEvent($request, $controller, new \ReflectionMethod($controller, 'insecureAction'));
        $handler->handleActionAnnotations($event);
        $this->assertNull($event->getResponse());
    }

    protected function getHandler(): Secure
    {
        return new Secure(Reader::createFromDefaults());
    }
}
