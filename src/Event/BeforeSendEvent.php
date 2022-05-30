<?php
namespace Croute\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fired before the response is sent, regardless if an action was invoked, can be used to modify the response before sending.
 */
final class BeforeSendEvent extends RouterEvent
{
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request);
        $this->response = $response;
    }
}
