<?php

namespace Croute\Attributes;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the Secure attribute, redirecting to https URLs if the incoming request was not secure
 */
final class SecureHandler extends BasicAttributeHandler
{
    public function getAttributeClass(): string
    {
        return Secure::class;
    }

    protected function handleRequest(RoutingAttribute $attribute, Request $request): ?Response
    {
        if (!$request->isSecure()) {
            $url = str_replace('http', 'https', $request->getSchemeAndHttpHost()) . $request->getRequestUri();
            return new RedirectResponse($url, 301);
        }
        return null;
    }
}
