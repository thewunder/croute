<?php
namespace Croute\Fixtures\Controller;

use Croute\Controller;
use Symfony\Component\HttpFoundation\Response;

class RouterTestController extends Controller
{
    public function indexAction()
    {
    }

    public function paramsAction($required, $optional = 'defaultValue')
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
