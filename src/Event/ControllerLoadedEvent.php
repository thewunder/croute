<?php
namespace Croute\Event;

use Croute\ControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Used after the controller is instantiated, you may replace the controller by setting a new controller
 */
final class ControllerLoadedEvent extends RouterEvent
{
    public function __construct(Request $request, private ControllerInterface $controller)
    {
        parent::__construct($request);
    }

    public function getController(): ControllerInterface
    {
        return $this->controller;
    }

    public function setController(ControllerInterface $controller): void
    {
        $this->controller = $controller;
    }
}
