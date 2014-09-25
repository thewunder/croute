<?php
namespace Croute\Event;

use Symfony\Component\HttpFoundation\Request;

/**
 * Fired as soon as the routing process starts
 * @package Croute
 */
class RequestEvent extends RouterEvent
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}