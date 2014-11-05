Croute
======

[![Build Status](https://api.travis-ci.org/thewunder/croute.svg?branch=master)](https://travis-ci.org/thewunder/croute)
[![Coverage Status](https://img.shields.io/coveralls/thewunder/croute.svg)](https://coveralls.io/r/thewunder/croute)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/11efe3a2-9566-4904-8e40-0d69efed7b02/mini.png)](https://insight.sensiolabs.com/projects/11efe3a2-9566-4904-8e40-0d69efed7b02)

Convention based routing for PHP based on Symfony components.

Croute is great because:

* You don't need to maintain a routing table
* Promotes consistent code organization
* Allows for customization through annotations and events

Your index.php should look something like this:

    $router = Router::create($eventDispatcher, ['Your\\Controller\\Namespace'], [$dependency1, $dependency2]);
    $router->route($request);

Your controllers should look something like this:

    namespace Your\Controller\Namespace
    
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

* http://yourdomain/my/ -> Your\Controller\Namespace\MyController::indexAction()
* http://yourdomain/my/action -> Your\Controller\Namespace\MyController::actionAction()

It supports nested namespaces so that:

* http://yourdomain/level1/level2/save -> Your\Controller\Namespace\Level1\Level2\IndexController::saveAction()

Annotations
-----------

Croute optionally supports controller and action annotations through the excellent [minime/annotations](https://github.com/marcioAlmada/annotations)
library.  To add an annotation handler simply:

    $router->addAnnotationHandler($myhandler);

Two annotations are included (but must be added) out of the box @httpMethod and @secure.

### @httpMethod

Restricts the allowed http methods.  Returns a 400 response if the method does not match.
 
    /**
     * @httpMethod POST
     */
    public function saveAction()
    
### @secure

Requires a secure connection.  If the connection is not https send a 301 redirect to the same url with the https protocol.

    /**
     * @secure
     */
    class IndexController extends Controller
    {

Events
------

Symfony events are dispatched for every step in the routing process.  A total of 12 events are dispatched in a
successful request:

1. router.request
1. router.controller_loaded
1. router.controller_loaded.Index
1. router.before_action
1. router.before_action.Index
1. router.before_action.Index.indexAction
1. router.after_action
1. router.after_action.Index
1. router.after_action.Index.indexAction
1. router.response_sent
1. router.response_sent.Index
1. router.response_sent.Index.indexAction

At any time before the response is sent, in an event listener you can set a response on the event to bypass the action and send instead.

    public function myListener(ControllerLoadedEvent $event)
    {
        $event->setResponse(new Response('PermissionDenied', 403));
    }

Error Handling
--------------

Proper error handling is not really something that I can do for you.  It's up to you to determine how to do logging, how and when to render a pretty error page.
To handle errors, implement the EventHandlerInterface and set your error handler on the router.  Your class will be called when common routing events occur
(i.e. 404 errors) and when there is an exception during the routing process.

Contributions
-------------

Yes please!  This library is currently a one man show, and it works great for me.  Please let me know if you have any ideas on improving croute.
