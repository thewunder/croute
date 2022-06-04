<?php

namespace Croute\Attributes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An attribute handler that does the same thing if used on a controller or action.
 */
abstract class BasicAttributeHandler implements AttributeHandlerInterface
{
    public function handleController(RoutingAttribute $attribute, Request $request, \ReflectionClass $controllerClass): ?Response
    {
        return $this->handleRequest($attribute, $request);
    }

    public function handleAction(RoutingAttribute $attribute, Request $request, \ReflectionMethod $actionMethod): ?Response
    {
        return $this->handleRequest($attribute, $request);
    }

    protected abstract function handleRequest(RoutingAttribute $attribute, Request $request): ?Response;
}
