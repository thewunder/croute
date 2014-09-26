<?php
namespace Croute;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use org\bovigo\vfs\vfsStreamWrapper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetResponse()
    {
        $controller = $this->getController();
        $this->assertTrue($controller->getRequest() instanceof Request);
    }

    public function testJson()
    {
        $controller = $this->getController();
        $json = $this->getControllerMethod($controller, 'json');
        $this->assertTrue($json->invoke($controller, []) instanceof JsonResponse);
    }

    public function testRedirect()
    {
        $controller = $this->getController();
        $redirect = $this->getControllerMethod($controller, 'redirect');
        /** @var RedirectResponse $response */
        $response = $redirect->invoke($controller, 'http://localhost/test');
        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertEquals('http://localhost/test', $response->headers->get('Location'));
    }

    public function testNotFound()
    {
        $controller = $this->getController();
        $notFound = $this->getControllerMethod($controller, 'notFound');
        /** @var Response $response */
        $response = $notFound->invoke($controller, 'Testing');
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Testing', $response->getContent());
    }

    public function testFileDownload()
    {
        $controller = $this->getController();
        $download = $this->getControllerMethod($controller, 'fileDownload');

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('unitTest'));
        vfsStreamWrapper::getRoot()->addChild(new vfsStreamFile('unitText.txt'));

        /** @var Response $response */
        $response = $download->invoke($controller, vfsStream::url('unitTest/unitText.txt'));
        $this->assertTrue($response instanceof BinaryFileResponse);
        $this->assertEquals('attachment; filename="unitText.txt"', $response->headers->get('Content-Disposition'));
    }

    /**
     * @return TestController
     */
    protected function getController()
    {
        $controller = new TestController();
        $controller->setRequest(Request::create('/'));
        return $controller;
    }

    /**
     * @param Controller $controller
     * @return \ReflectionMethod
     */
    protected function getControllerMethod(Controller $controller, $method)
    {
        $method = new \ReflectionMethod($controller, $method);
        $method->setAccessible(true);
        return $method;
    }
}

class TestController extends Controller
{
}