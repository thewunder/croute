<?php
namespace Croute\Test\Fixtures\Controller;

use Croute\Attributes\Secure;
use Croute\Controller;
use Symfony\Component\HttpFoundation\Response;

class RouterTestController extends Controller
{
    public function indexAction()
    {
    }

    public function paramsAction($required, $optional = 'defaultValue')
    {
        return $this->json(['required'=>$required, 'optional'=>$optional]);
    }

    public function echoAction($input)
    {
        return new Response($input);
    }

    public function returnAction()
    {
        return new Response('Hello');
    }

    #[Secure]
    public function secureAction()
    {
        return new Response('Secure stuff');
    }

    public function exceptionAction()
    {
        throw new \RuntimeException('Explode');
    }

    protected function protectedAction()
    {
    }
}
