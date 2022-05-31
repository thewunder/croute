<?php
namespace Croute\Attributes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If a controller or action has the HttpMethod attribute return a 405 response if the http method is not in the value,
 * multiple values are allowed.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class HttpMethod implements RoutingAttribute
{
    private array $methods;

    public function __construct(string $method, string ...$moreMethods)
    {
        $methods = [$method, ...$moreMethods];
        $this->methods = array_map('strtoupper', $methods);
    }

    public function handleRequest(Request $request): ?Response
    {
        if (!in_array($request->getMethod(), $this->methods)) {
            return new Response('Invalid http method', Response::HTTP_METHOD_NOT_ALLOWED);
        }
        return null;
    }
}
