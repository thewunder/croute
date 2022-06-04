<?php

namespace Croute\Test\Attributes;

use Croute\Attributes\SecureHandler;
use Croute\Attributes\Secure;
use Croute\Test\Fixtures\Controller\IndexController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureTest extends TestCase
{
    public function testInSecureController()
    {
        $response = (new SecureHandler())->handleController(new Secure(), Request::create('http://localhost/'), new \ReflectionClass(IndexController::class));
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testSecureController()
    {
        $response = (new SecureHandler())->handleController(new Secure(), Request::create('https://localhost/'), new \ReflectionClass(IndexController::class));
        $this->assertNull($response);
    }
}
