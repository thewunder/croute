<?php
namespace Croute\Event;

use Croute\Controller;
use Croute\ControllerInterface;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

/**
 * Used prior to invoking the action
 * @package Croute
 */
class BeforeActionEvent extends RouterEvent
{
    /** @var ControllerInterface */
    protected $controller;

    /** @var ReflectionMethod */
    protected $method;

    public function __construct(Request $request, ControllerInterface $controller, ReflectionMethod $method)
    {
        $this->request = $request;
        $this->controller = $controller;
        $this->method = $method;
    }

    /**
     * @return ControllerInterface
     */
    public function getController(): ControllerInterface
    {
        return $this->controller;
    }

    /**
     * @return ReflectionMethod
     */
    public function getMethod(): ReflectionMethod
    {
        return $this->method;
    }
}
