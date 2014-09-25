<?php
namespace Croute\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * By setting the response on any of the child classes of this class, you may stop the routing process and simply return the response.
 *
 * @package Croute
 */
abstract class RouterEvent extends Event
{

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
} 