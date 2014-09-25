<?php
namespace Croute;

use Croute\Event\BeforeActionEvent;
use Croute\Event\ControllerLoadedEvent;
use Croute\Event\RouterEvent;
use Minime\Annotations\Interfaces\ReaderInterface;

abstract class AnnotationHandler implements AnnotationHandlerInterface
{
    /**
     * @var \Minime\Annotations\Interfaces\ReaderInterface
     */
    protected $reader;

    /** @var string */
    protected $annotation;

    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
        if(!$this->annotation) {
            throw new \LogicException('You must specify an annotation to handle');
        }
    }

    public function getName()
    {
        return $this->annotation;
    }

    public function handleControllerAnnotations(ControllerLoadedEvent $event)
    {
        $value = $this->reader->getClassAnnotations($event->getController())->get($this->annotation);
        $this->handleAnnotation($value, $event);
    }

    public function handleActionAnnotations(BeforeActionEvent $event)
    {
        $value = $this->reader->getAnnotations($event->getMethod())->get($this->annotation);
        $this->handleAnnotation($value, $event);
    }

    protected abstract function handleAnnotation($value, RouterEvent $event);

    public static function getSubscribedEvents()
    {
        return [
            'router.controller_loaded'  => 'handleControllerAnnotations',
            'router.before_action'      => 'handleActionAnnotations'
        ];
    }
}