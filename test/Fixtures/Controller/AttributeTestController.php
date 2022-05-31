<?php
namespace Croute\Test\Fixtures\Controller;

use Croute\Attributes\Secure;
use Croute\Controller;

#[Secure]
class AttributeTestController extends Controller
{
}
