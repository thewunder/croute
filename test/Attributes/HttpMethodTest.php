<?php

namespace Croute\Test\Attributes;

use Croute\Attributes\HttpMethodHandler;
use Croute\Attributes\HttpMethod;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpMethodTest extends TestCase
{
    public function testMethodMatch(): void
    {
        $attribute = new HttpMethod('post');
        $request = Request::create('/', 'POST');
        $response = (new HttpMethodHandler())->handleAction($attribute, $request, new \ReflectionMethod(__METHOD__));
        $this->assertNull($response);
    }

    public function testMultiMatch(): void
    {
        $attribute = new HttpMethod('post', 'put');
        $request = Request::create('/', 'POST');
        $response = (new HttpMethodHandler())->handleAction($attribute, $request, new \ReflectionMethod(__METHOD__));
        $this->assertNull($response);

        $request = Request::create('/', 'PUT');
        $response = (new HttpMethodHandler())->handleAction($attribute, $request, new \ReflectionMethod(__METHOD__));
        $this->assertNull($response);
    }

    public function testNoMatch(): void
    {
        $attribute = new HttpMethod('post');
        $request = Request::create('/');
        $response = (new HttpMethodHandler())->handleAction($attribute, $request, new \ReflectionMethod(__METHOD__));
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }
}
