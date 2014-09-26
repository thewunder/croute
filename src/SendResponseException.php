<?php
namespace Croute;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class SendResponseException
 * @package Croute
 */
class SendResponseException extends \RuntimeException
{
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}