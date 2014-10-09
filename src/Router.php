<?php
namespace Croute;

use Croute\Annotation\AnnotationHandlerInterface;
use Croute\Event\AfterActionEvent;
use Croute\Event\AfterSendEvent;
use Croute\Event\BeforeActionEvent;
use Croute\Event\ControllerLoadedEvent;
use Croute\Event\RequestEvent;
use Croute\Event\RouterEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Primary entry point for routing
 */
class Router
{
    /** @var ControllerFactoryInterface */
    protected $controllerFactory;

    /** @var EventDispatcher */
    protected $dispatcher;

    /** @var ErrorHandlerInterface */
    protected $errorHandler;

    /** @var AnnotationHandlerInterface[]  */
    protected $annotationHandlers = [];

    /**
     * Returns an instance using the default controller factory implementation
     *
     * @param EventDispatcher $dispatcher
     * @param array $controllerNamespaces Namespaces to search for controller classes
     * @param array $controllerDependencies Dependencies passed to controller constructor
     * @return Router
     */
    public static function create(EventDispatcher $dispatcher, array $controllerNamespaces, array $controllerDependencies = [])
    {
        return new static(new ControllerFactory($controllerNamespaces, $controllerDependencies), $dispatcher);
    }

    public function __construct(ControllerFactoryInterface $controllerFactory, EventDispatcher $dispatcher)
    {
        $this->controllerFactory = $controllerFactory;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param AnnotationHandlerInterface $handler
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addAnnotationHandler(AnnotationHandlerInterface $handler)
    {
        if(isset($this->annotationHandlers[$handler->getName()])) {
            throw new \InvalidArgumentException($handler->getName() . ' is already registered');
        }

        $this->annotationHandlers[$handler->getName()] = $handler;
        $this->dispatcher->addSubscriber($handler);
        return $this;
    }

    /**
     * @param $name
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function removeAnnotationHandler($name)
    {
        if(!isset($this->annotationHandlers[$name])) {
            throw new \InvalidArgumentException($name . ' is not registered');
        }

        $handler = $this->annotationHandlers[$name];
        $this->dispatcher->removeSubscriber($handler);
        unset($this->annotationHandlers[$name]);
        return $this;
    }

    /**
     * @return ControllerFactoryInterface
     */
    public function getControllerFactory()
    {
        return $this->controllerFactory;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function route(Request $request)
    {
        $requestEvent = new RequestEvent($request);
        $response = $this->dispatchEvent('router.request', $requestEvent);
        if($response) {
            return $this->sendResponse($request, $response);
        }

        $controllerName = $this->controllerFactory->getControllerName($request);
        $request->attributes->set('controller', $controllerName);
        $controller = $this->controllerFactory->getController($request, $controllerName);

        if(!$controller) {
            $response = $this->handleError('Unable to load '.$request->attributes->get('controller').'Controller.', 404);
        } else {
            $controller->setRequest($request);
            $controllerEvent = new ControllerLoadedEvent($request, $controller);
            $response = $this->dispatchEvent('router.controller_loaded', $controllerEvent);
            if($response) {
                return $this->sendResponse($request, $response);
            }

            $actionMethod = $this->matchAction($controller, $request);
            if(!$actionMethod) {
                $controllerName = $request->attributes->get('controller');
                $action = $request->attributes->get('action');
                $response = $this->handleError("Method $action not found on {$controllerName}Controller", 404);
            } else {
                $response = $this->invokeAction($controller, $actionMethod, $request);
            }
        }

        return $this->sendResponse($request, $response);
    }

    /**
     * @param ControllerInterface $controller
     * @param Request $request
     * @return null|string
     */
    protected function matchAction(ControllerInterface $controller, Request $request)
    {
        $path = $request->getPathInfo();
        $action = substr($path, strrpos($path, '/') + 1);
        if(!$action) {
            $action = 'index';
        }

        $request->attributes->set('action', $action);
        $actionName = $action. 'Action';
        if(method_exists($controller, $actionName)) {
            return $actionName;
        }
        return null;
    }

    /**
     * @param ControllerInterface $controller
     * @param string $actionMethod
     * @param Request $request
     * @throws \Exception
     * @return Response
     */
    protected function invokeAction(ControllerInterface $controller, $actionMethod, Request $request)
    {
        ob_start();
        $method = new \ReflectionMethod($controller, $actionMethod);

        if(!$method->isPublic()) {
            $controllerName = $request->attributes->get('controller');
            ob_end_clean();
            return $this->handleError("Method '{$actionMethod}' on {$controllerName}Controller is not public", 500);
        }

        $beforeEvent = new BeforeActionEvent($request, $controller, $method);
        $this->dispatchEvent('router.before_action', $beforeEvent);

        $params = [];
        foreach($method->getParameters() as $parameter) {
            $value = $request->get($parameter->getName());
            if($value === null && !$parameter->isOptional()) {
                ob_end_clean();
                return $this->handleError("Missing required parameter '{$parameter->getName()}'", 400);
            }
            $params[] = $value;
        }

        $response = null;
        try {
            $response = $method->invokeArgs($controller, $params);
        } catch(\Exception $e) {
            if($this->errorHandler) {
                $response = $this->errorHandler->handleException($e);
            } else {
                ob_end_clean();
                throw $e;
            }
        }

        if(!$response instanceof Response) {
            $response = new Response(ob_get_clean());
        } else {
            ob_end_clean();
        }

        $afterEvent = new AfterActionEvent($request, $response);
        $this->dispatchEvent('router.after_action', $afterEvent);

        return $afterEvent->getResponse();
    }

    /**
     * @param ErrorHandlerInterface $errorController
     * @return $this
     */
    public function setErrorHandler(ErrorHandlerInterface $errorController)
    {
        $this->errorHandler = $errorController;
        return $this;
    }

    /**
     * Dispatches the event and the following related events:
     *
     * event_name.controllerName (if the controller name has been determined)
     * event_name.controllerName.action (if the action name has been determined)
     *
     * Then handles returning a response immediately if the listener sets the response.
     *
     * @param $eventName
     * @param RouterEvent $event
     * @throws \Exception
     * @return Response
     */
    private function dispatchEvent($eventName, RouterEvent $event)
    {
        try {
            $this->dispatcher->dispatch($eventName, $event);
            $request = $event->getRequest();
            if($request->attributes->has('controller')) {
                $controllerName = $request->attributes->get('controller');
                $this->dispatcher->dispatch($eventName . ".$controllerName", $event);
                if($request->attributes->has('action')) {
                    $actionName = $request->attributes->get('action');
                    $this->dispatcher->dispatch($eventName . ".$controllerName.$actionName", $event);
                }
            }
        } catch(\Exception $e) {
            if($this->errorHandler) {
                $response = $this->errorHandler->handleException($e);
                if($response instanceof Response) {
                    $event->setResponse($response);
                }
            } else {
                throw $e;
            }
        }

        if($event->getResponse() && $eventName != 'router.after_action' && $eventName != 'router.response_sent') {
            return $event->getResponse();
        }

        return null;
    }

    /**
     * @param $message
     * @param int $code
     * @return Response
     */
    protected function handleError($message, $code = 404)
    {
        if(!$this->errorHandler) {
            $response = new Response(Response::$statusTexts[$code], $code);
        } else {
            $response = $this->errorHandler->displayErrorPage($code, $message);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param $response
     * @return Response
     */
    protected function sendResponse(Request $request, Response $response)
    {
        $response->send();
        $this->dispatchEvent('router.response_sent', new AfterSendEvent($request, $response));
        return $response;
    }
}
