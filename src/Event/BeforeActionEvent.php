<?php
namespace Croute\Event;

use Croute\Controller;
use Croute\ControllerInterface;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

/**
 * Used prior to invoking the action
 */
final class BeforeActionEvent extends RouterEvent
{
    public function __construct(Request $request, private readonly ControllerInterface $controller, private readonly ReflectionMethod $method)
    {
        parent::__construct($request);
    }

    public function getController(): ControllerInterface
    {
        return $this->controller;
    }

    public function getMethod(): ReflectionMethod
    {
        return $this->method;
    }
}
