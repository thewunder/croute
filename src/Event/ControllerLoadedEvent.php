<?php
namespace Croute\Event;

use Croute\Controller;
use Croute\ControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Used after the controller is instantiated, you may replace the controller by setting a new controller
 * @package Croute
 */
class ControllerLoadedEvent extends RouterEvent
{
    /** @var Controller */
    protected $controller;

    public function __construct(Request $request, ControllerInterface $controller)
    {
        $this->request = $request;
        $this->controller = $controller;
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param ControllerInterface $controller
     */
    public function setController(ControllerInterface $controller)
    {
        $this->controller = $controller;
    }
}
