<?php
namespace Croute;

use Symfony\Component\HttpFoundation\Request;

class ControllerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testIndexController()
    {
        $factory = $this->getFactory();

        $controller = $factory->getController(Request::create('/'));
        $this->assertTrue($controller instanceof IndexController);
    }

    public function testNamedController()
    {
        $factory = $this->getFactory();

        $controller = $factory->getController(Request::create('/named/'));
        $this->assertTrue($controller instanceof NamedController);
    }

    public function testSanitization()
    {
        $factory = $this->getFactory();

        $controller = $factory->getController(Request::create('/nam..\..ed/'));
        $this->assertTrue($controller instanceof NamedController);
    }

    public function testNamespacedControllers()
    {
        $factory = $this->getFactory();

        $controller = $factory->getController(Request::create('/myNamespace/'));
        $this->assertTrue($controller instanceof \Croute\MyNamespace\IndexController);

        $controller = $factory->getController(Request::create('/myNamespace/named/'));
        $this->assertTrue($controller instanceof \Croute\MyNamespace\NamedController);
    }

    public function testControllerNotFound()
    {
        $factory = $this->getFactory();

        $controller = $factory->getController(Request::create('/asdf/'));
        $this->assertNull($controller);
    }

    protected function getFactory()
    {
        return new ControllerFactory(['Croute'], []);
    }
}

class IndexController extends Controller
{
}

class NamedController extends Controller
{
}

namespace Croute\MyNamespace;

use Croute\Controller;

class IndexController extends Controller
{
}

class NamedController extends Controller
{
}