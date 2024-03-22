<?php
namespace Croute;

use Symfony\Component\HttpFoundation\Response;

interface ErrorHandlerInterface
{
    /**
     * @param string|null $message
     * @return Response
     */
    public function displayErrorPage(int $code, string $message = null): Response;

    /**
     * Convert exceptions into a http response
     *
     * @return Response|null
     */
    public function handleException(\Throwable $e): ?Response;
}
