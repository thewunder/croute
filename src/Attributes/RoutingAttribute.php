<?php
namespace Croute\Attributes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An attribute used for the purposes of decorating controllers or actions
 */
interface RoutingAttribute
{
    /**
     * @param Request $request
     * @return Response|null Returning a response will immediately return that response instead of processing the action
     */
    public function handleRequest(Request $request): ?Response;
}
