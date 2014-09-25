<?php
namespace Croute;

use Symfony\Component\HttpFoundation\Response;

interface ErrorHandlerInterface
{
    /**
     * @param int $code
     * @param null|string $message
     * @return Response
     */
    public function displayErrorPage($code, $message = null);

    /**
     * @param \Exception $e
     * @return Response
     */
    public function handleException(\Exception $e);
}