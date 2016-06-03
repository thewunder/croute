<?php
namespace Croute;

use Symfony\Component\HttpFoundation\Request;

interface ControllerFactoryInterface
{
    /**
     * Returns the name of the controller to load, used in naming events
     *
     * @param Request $request
     * @return string
     */
    public function getControllerName(Request $request);

    /**
     * @param Request $request
     * @param string $controllerName The name from getControllerName
     * @return ControllerInterface
     */
    public function getController(Request $request, $controllerName);
}
