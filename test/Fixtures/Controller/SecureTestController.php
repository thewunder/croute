<?php
namespace Croute\Fixtures\Controller;

use Croute\Controller;

/**
 * @secure
 */
class SecureTestController extends Controller
{
    /**
     * @secure
     */
    public function secureAction()
    {
    }

    public function insecureAction()
    {
    }
}
