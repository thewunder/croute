<?php

namespace Croute\Test\Attributes;

use Croute\Attributes\HttpMethod;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpMethodTest extends TestCase
{
    public function testMethodMatch()
    {
        $attribute = new HttpMethod('post');
        $request = Request::create('/', 'POST');
        $response = $attribute->handleRequest($request);
        $this->assertNull($response);
    }

    public function testMultiMatch()
    {
        $attribute = new HttpMethod('post', 'put');
        $request = Request::create('/', 'POST');
        $response = $attribute->handleRequest($request);
        $this->assertNull($response);

        $request = Request::create('/', 'PUT');
        $response = $attribute->handleRequest($request);
        $this->assertNull($response);
    }

    public function testNoMatch()
    {
        $attribute = new HttpMethod('post');
        $request = Request::create('/');
        $response = $attribute->handleRequest($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }
}
