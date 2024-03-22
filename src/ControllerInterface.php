<?php
namespace Croute;

use Symfony\Component\HttpFoundation\Request;

interface ControllerInterface
{
    /**
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * @return void
     */
    public function setRequest(Request $request): void;
}
