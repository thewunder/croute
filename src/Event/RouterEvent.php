<?php
namespace Croute\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * By setting the response on any of the child classes of this class, you may stop the routing process and simply return the response.
 */
abstract class RouterEvent extends Event
{
    protected ?Response $response = null;

    public function __construct(protected Request $request)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
