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
    public function getControllerName(Request $request): string;

    /**
     * @param Request $request
     * @param string $controllerName The name from getControllerName
     * @return ControllerInterface|null
     */
    public function getController(Request $request, string $controllerName): ?ControllerInterface;
}
