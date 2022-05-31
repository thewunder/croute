<?php

namespace Croute\Test\Attributes;

use Croute\Attributes\Secure;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureTest extends TestCase
{
    public function testInSecureController()
    {
        $response = (new Secure())->handleRequest(Request::create('http://localhost/'));
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testSecureController()
    {
        $response = (new Secure())->handleRequest(Request::create('https://localhost/'));
        $this->assertNull($response);
    }
}
