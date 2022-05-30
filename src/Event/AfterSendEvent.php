<?php
namespace Croute\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used after the response is sent.
 */
final class AfterSendEvent extends RouterEvent
{
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request);
        $this->response = $response;
    }

    /**
     * @param Response $response
     * @throws \BadMethodCallException
     */
    public function setResponse(Response $response): void
    {
        throw new \BadMethodCallException('The response has already been sent and cannot be modified');
    }
}
