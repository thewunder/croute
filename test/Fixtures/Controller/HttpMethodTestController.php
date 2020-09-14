<?php
namespace Croute\Test\Fixtures\Controller;

use Croute\Controller;

/**
 * @httpMethod post
 */
class HttpMethodTestController extends Controller
{

    public function noAnnotationAction()
    {
    }

    /**
     * @httpMethod DELETE
     */
    public function singleAnnotationAction()
    {
    }

    /**
     * @httpMethod ["PUT", "POST"]
     */
    public function multipleAnnotationAction()
    {
    }

    /**
     * @httpMethod
     */
    public function noHttpMethodSpecifiedAction()
    {
    }
}
