<?php
namespace Croute\Attributes;

/**
 * Must be served over https, and redirects to https if the request was not secure
 */
#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
final class Secure implements RoutingAttribute
{
}
