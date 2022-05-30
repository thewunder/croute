<?php
namespace Croute;

use Croute\Annotation\AnnotationHandlerInterface;
use Croute\Event\AfterActionEvent;
use Croute\Event\AfterSendEvent;
use Croute\Event\BeforeActionEvent;
use Croute\Event\BeforeSendEvent;
use Croute\Event\ControllerLoadedEvent;
use Croute\Event\RequestEvent;
use Croute\Event\RouterEvent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Primary entry point for routing
 */
class Router
{
    /** @var ControllerFactoryInterface */
    protected $controllerFactory;

    /** @var EventDispatcherInterface */
    protected EventDispatcherInterface $dispatcher;

    /** @var ErrorHandlerInterface */
    protected $errorHandler;

    /** @var AnnotationHandlerInterface[]  */
    protected $annotationHandlers = [];

    /** @var RouteCollection */
    protected $routes;

    /**
     * Returns an instance using the default controller factory implementation
     *
     * @param EventDispatcherInterface $dispatcher
     * @param array $controllerNamespaces Namespaces to search for controller classes
     * @param array $controllerDependencies If the container is either not provided or does not have the class these will be passed to controller class constructors
     * @param ContainerInterface|null $container PSR-11 Container to use to instantiate controllers, the full class name must resolve to an instance of the controller class
     * @return Router
     */
    public static function create(EventDispatcherInterface $dispatcher, array $controllerNamespaces, array $controllerDependencies = [], ContainerInterface $container = null)
    {
        return new static(new ControllerFactory($controllerNamespaces, $controllerDependencies, $container), $dispatcher);
    }

    public function __construct(ControllerFactoryInterface $controllerFactory, EventDispatcherInterface $dispatcher)
    {
        $this->controllerFactory = $controllerFactory;
        $this->dispatcher = $dispatcher;
        $this->routes = new RouteCollection();
    }

    /**
     * Adds a custom route
     *
     * Example:
     * $router->addRoute('/myCustomPath/{param1}', 'GET', 'Namespace\\Custom', 'special')
     *
     * Will call \\ControllerNamespace\\Namespace\\CustomController::specialAction($param1) for GET requests
     *
     *
     * @param string $path
     * @param string|array $methods A required HTTP method or an array of methods
     * @param string $controller Controller class minus the controller namespace and 'Controller'
     * @param string|null $action Action name if omitted last part of path will be used to determine the action
     *
     * @return $this
     */
    public function addRoute(string $path, $methods, string $controller, string $action = null): self
    {
        if($action) {
            $controller = $controller . '::' . $action;
        }
        $route = new Route($path, ['_controller'=>$controller], [], [], '', [], $methods);

        if(is_array($methods)) {
            $methods = implode('|', $methods);
        }
        $this->routes->add($path.':'.$methods, $route);
        return $this;
    }

    /**
     * Add multiple routes at once
     *
     * @see addRoute for format and example
     *
     * @param array $routes
     */
    public function addRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            $count = count($route);
            if($count < 3 || $count > 4) {
                throw new \InvalidArgumentException('Each route must be: path, method(s), controller, [action]');
            }
            call_user_func_array([$this, 'addRoute'], $route);
        }
    }

    /**
     * Adds a custom route by providing a symfony Route object. Allows for many more other options compared to addRoute().
     *
     * Example:
     * $router->addCustomRoute('custom_route',
     * new Route('/myCustomPath/{param1}', ['_controller'=>'Namespace\\Custom::special']))
     *
     * Will call \\ControllerNamespace\\Namespace\\CustomController::specialAction($param1) for all HTTP request methods
     *
     *
     * @param string $name
     * @param Route $route
     * @return $this
     */
    public function addCustomRoute(string $name, Route $route): self
    {
        if(!$route->getDefault('_controller')) {
            throw new \InvalidArgumentException('You must specify a _controller');
        }

        $this->routes->add($name, $route);
        return $this;
    }

    /**
     * @param AnnotationHandlerInterface $handler
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addAnnotationHandler(AnnotationHandlerInterface $handler): self
    {
        if (isset($this->annotationHandlers[$handler->getName()])) {
            throw new \InvalidArgumentException($handler->getName() . ' is already registered');
        }

        $this->annotationHandlers[$handler->getName()] = $handler;
        $this->dispatcher->addSubscriber($handler);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function removeAnnotationHandler(string $name): self
    {
        if (!isset($this->annotationHandlers[$name])) {
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
    public function getControllerFactory(): ControllerFactoryInterface
    {
        return $this->controllerFactory;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function route(Request $request): Response
    {
        $requestEvent = new RequestEvent($request);
        $response = $this->dispatchEvent('router.request', $requestEvent);
        if ($response) {
            return $this->sendResponse($request, $response);
        }

        $controllerName = null;
        $actionMethod = null;

        $matcher = $this->getUrlMatcher($request);
        try {
            $match = $matcher->match($request->getPathInfo());
            $controllerName = $match['_controller'];
            if(strpos($controllerName, '::') !== false) {
                list($controllerName, $actionMethod) = explode('::', $controllerName);
                if(strrpos($actionMethod, 'Action') === false) {
                    $actionMethod .= 'Action';
                }
            }
            foreach ($match as $key => $value) {
                $request->attributes->set($key, $value);
            }
        } catch (ResourceNotFoundException $notFoundException) {
            $controllerName = $this->controllerFactory->getControllerName($request);
        } catch (MethodNotAllowedException $notAllowedException) {
            return $this->sendResponse($request, new Response('Invalid http method', Response::HTTP_METHOD_NOT_ALLOWED));
        }

        $request->attributes->set('controller', $controllerName);
        $controller = $this->controllerFactory->getController($request, $controllerName);

        if (!$controller) {
            $response = $this->handleError('Unable to load '.$request->attributes->get('controller').'Controller.', 404);
        } else {
            $controller->setRequest($request);
            $controllerEvent = new ControllerLoadedEvent($request, $controller);
            $response = $this->dispatchEvent('router.controller_loaded', $controllerEvent);
            if ($response) {
                return $this->sendResponse($request, $response);
            }

            if(!$actionMethod) {
                $actionMethod = $this->matchAction($controller, $request);
            }

            if (!$actionMethod) {
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
     * @param Request $request
     * @return UrlMatcherInterface
     */
    protected function getUrlMatcher(Request $request): UrlMatcherInterface
    {
        $context = new RequestContext();
        return new UrlMatcher($this->routes, $context->fromRequest($request));
    }

    /**
     * @param ControllerInterface $controller
     * @param Request $request
     * @return null|string
     */
    protected function matchAction(ControllerInterface $controller, Request $request): ?string
    {
        $path = $request->getPathInfo();
        $action = substr($path, strrpos($path, '/') + 1);
        if (!$action) {
            $action = 'index';
        }

        $request->attributes->set('action', $action);
        $actionName = $action. 'Action';
        if (method_exists($controller, $actionName)) {
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
    protected function invokeAction(ControllerInterface $controller, string $actionMethod, Request $request): Response
    {
        $method = new \ReflectionMethod($controller, $actionMethod);

        if (!$method->isPublic()) {
            $controllerName = $request->attributes->get('controller');
            return $this->handleError("Method '{$actionMethod}' on {$controllerName}Controller is not public", 500);
        }

        $beforeEvent = new BeforeActionEvent($request, $controller, $method);
        $response = $this->dispatchEvent('router.before_action', $beforeEvent);
        if ($response) {
            return $response;
        }

        $params = [];
        foreach ($method->getParameters() as $parameter) {
            $value = $request->get($parameter->getName());
            if ($value === null) {
                if ($parameter->isOptional()) {
                    $value = $parameter->getDefaultValue();
                } else {
                    return $this->handleError("Missing required parameter '{$parameter->getName()}'", 400);
                }
            }
            $params[] = $value;
        }

        ob_start();
        $response = null;
        try {
            $response = $method->invokeArgs($controller, $params);
        } catch (\Exception $e) {
            if ($this->errorHandler) {
                $response = $this->errorHandler->handleException($e);
            } else {
                ob_end_clean();
                throw $e;
            }
        }

        if (!$response instanceof Response) {
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
    public function setErrorHandler(ErrorHandlerInterface $errorController): self
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
     * @return Response
     *
     * @throws \Throwable
     */
    private function dispatchEvent($eventName, RouterEvent $event): ?Response
    {
        try {
            $this->dispatcher->dispatch($event, $eventName);
            $request = $event->getRequest();
            if ($request->attributes->has('controller')) {
                $controllerName = $request->attributes->get('controller');
                $this->dispatcher->dispatch($event, $eventName . ".$controllerName");
                if ($request->attributes->has('action')) {
                    $actionName = $request->attributes->get('action');
                    $this->dispatcher->dispatch($event, $eventName . ".$controllerName.$actionName");
                }
            }
        } catch (\Throwable $e) {
            if ($this->errorHandler) {
                $response = $this->errorHandler->handleException($e);
                if ($response instanceof Response) {
                    $event->setResponse($response);
                }
            } else {
                throw $e;
            }
        }

        if ($event->getResponse() && $eventName != 'router.after_action' && $eventName != 'router.response_sent') {
            return $event->getResponse();
        }

        return null;
    }

    /**
     * @param $message
     * @param int $code
     * @return Response
     */
    protected function handleError($message, $code = 404): Response
    {
        if (!$this->errorHandler) {
            $response = new Response(Response::$statusTexts[$code], $code);
        } else {
            $response = $this->errorHandler->displayErrorPage($code, $message);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function sendResponse(Request $request, Response $response): Response
    {
        $this->dispatcher->dispatch(new BeforeSendEvent($request, $response), 'router.before_response_sent');
        $response->prepare($request);
        $response->send();
        $this->dispatchEvent('router.response_sent', new AfterSendEvent($request, $response));
        return $response;
    }
}
