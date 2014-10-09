<?php
namespace Croute\Annotation;

use Croute\Annotation\AnnotationHandler;
use Croute\Event\RouterEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * If a controller or action has "@secure" annotation, redirect to https if url is non-secure
 *
 * @package Croute\Listener
 */
class Secure extends AnnotationHandler
{
    protected $annotation = 'secure';

    protected function handleAnnotation($value, RouterEvent $event)
    {
        $request = $event->getRequest();
        if(!$request->isSecure() && $value) {
            $url = str_replace('http', 'https', $request->getSchemeAndHttpHost()) . $request->getRequestUri();
            $event->setResponse(new RedirectResponse($url, 301));
        }
    }
}
