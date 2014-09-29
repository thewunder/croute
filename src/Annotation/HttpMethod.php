<?php
namespace Croute\Annotation;

use Croute\Annotation\AnnotationHandler;
use Croute\Event\RouterEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * If a controller or action has the "@httpMethod POST" annotation return a 400 response if the http method is not in the value
 * multiple values are allowed
 */
class HttpMethod extends AnnotationHandler
{
    protected $annotation = 'httpMethod';

    protected function handleAnnotation($value, RouterEvent $event)
    {
        if($value) {
            if(is_bool($value)) {
                throw new \InvalidArgumentException('You must specify which HTTP method(s) you require.');
            }

            if(is_string($value)) {
                $value = array($value);
            }

            $value = array_map('strtoupper', $value);
            if(!in_array($event->getRequest()->getMethod(), $value)) {
                $event->setResponse(new Response('Invalid http method', 400));
            }
        }
    }
}