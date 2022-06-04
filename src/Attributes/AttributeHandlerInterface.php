<?php

namespace Croute\Attributes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Represents a class that handles controller and action attributes
 */
interface AttributeHandlerInterface
{
    /**
     * @return string Fully qualified class name of attribute this class handles
     */
    public function getAttributeClass(): string;

    /**
     * @param RoutingAttribute $attribute The attribute which may contain more specific data
     * @param Request $request The request object
     * @param \ReflectionClass $controllerClass The reflection class for the controller
     * @return Response|null Return a response to short circuit the rest of the routing process.
     */
    public function handleController(RoutingAttribute $attribute, Request $request, \ReflectionClass $controllerClass): ?Response;

    /**
     * @param RoutingAttribute $attribute
     * @param Request $request
     * @param \ReflectionMethod $actionMethod
     * @return Response|null
     */
    public function handleAction(RoutingAttribute $attribute, Request $request, \ReflectionMethod $actionMethod): ?Response;
}
