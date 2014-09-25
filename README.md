Croute
======

Convention based routing for PHP based on symfony components.

Your index.php should look something like this (except you should use a dependency injection container):

    $factory = new ControllerFactory(['Your\\Namespace\\Controller'], [$dependency1, $dependency2]);
    $router = new Router($factory, $eventDispatcher);
    $router->route($request);

Your controllers should look something like this:

    namespace Your\Namespace\Controller
    
    class IndexController extends Croute\Controller
    {
        public function __construct($dependency1, $dependency2)
        {
            //...
        }
        
        /**
         * Will be available at http://yourdomain/
         * and require the "required" (body or querystring) request parameter 
         */
        public function indexAction($required, $optional = null)
        {
            echo 'Crouter Controller'; //you can echo or return a symfony Response
        }
        
        /**
         * Available at http://yourdomain/test
         */
        public function testAction()
        {
            return new Response('Test Action');
        }
    }

The name of the controller determines which url it appears as:

> http://yourdomain/my/ -> Your\Namespace\Controller\MyController::indexAction()
http://yourdomain/my/action -> Your\Namespace\Controller\MyController::actionAction()

The default controller factory supports nested namespaces so that:

> http://yourdomain/level1/level2/save -> Your\Namespace\Controller\Level1\Level2\IndexController::saveAction()

