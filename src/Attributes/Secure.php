<?php
namespace Croute\Attributes;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Must be served over https, and redirects to https if the request was not secure
 */
#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
final class Secure implements RoutingAttribute
{
    public function handleRequest(Request $request): ?Response
    {
        if (!$request->isSecure()) {
            $url = str_replace('http', 'https', $request->getSchemeAndHttpHost()) . $request->getRequestUri();
            return new RedirectResponse($url, 301);
        }
        return null;
    }
}
