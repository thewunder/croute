<?php
namespace Croute;

use Symfony\Component\HttpFoundation\Response;

interface ErrorHandlerInterface
{
    /**
     * @param int $code
     * @param string|null $message
     * @return Response
     */
    public function displayErrorPage(int $code, string $message = null): Response;

    /**
     * @param \Exception $e
     * @return Response
     */
    public function handleException(\Exception $e): ?Response;
}
