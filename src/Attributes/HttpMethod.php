<?php
namespace Croute\Attributes;

/**
 * If a controller or action has the HttpMethod attribute return a 405 response if the http method is not in the value,
 * multiple values are allowed.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class HttpMethod implements RoutingAttribute
{
    /**
     * @var string[]
     */
    public array $methods;

    public function __construct(string $method, string ...$moreMethods)
    {
        $methods = [$method, ...$moreMethods];
        $this->methods = array_map('strtoupper', $methods);
    }
}
