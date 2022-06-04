<?php

namespace Croute\Attributes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HttpMethodHandler extends BasicAttributeHandler
{
    public function getAttributeClass(): string
    {
        return HttpMethod::class;
    }

    protected function handleRequest(HttpMethod|RoutingAttribute $attribute, Request $request): ?Response
    {
        if (!in_array($request->getMethod(), $attribute->methods)) {
            return new Response('Invalid http method', Response::HTTP_METHOD_NOT_ALLOWED);
        }
        return null;
    }
}
