<?php
namespace Croute\Annotation;

use Croute\Event\BeforeActionEvent;
use Croute\Event\ControllerLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface AnnotationHandlerInterface extends EventSubscriberInterface
{
    public function getName();

    public function handleControllerAnnotations(ControllerLoadedEvent $event);

    public function handleActionAnnotations(BeforeActionEvent $event);
}
