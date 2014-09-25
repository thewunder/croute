<?php
namespace Croute\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used after controller action has been invoked, can be used to modify the response before sending.
 * @package Croute
 */
class AfterActionEvent extends RouterEvent
{
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}