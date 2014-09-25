<?php
namespace Croute;


use Symfony\Component\HttpFoundation\Request;

interface ControllerFactoryInterface
{
    /**
     * @param Request $request
     * @return ControllerInterface
     */
    public function getController(Request $request);
} 