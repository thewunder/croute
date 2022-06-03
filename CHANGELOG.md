Version 2.0
===========

Breaking Changes
------------
* Require PHP 8
* Require a PSR-11 compatible DI container
* Replace Annotations with php 8 attributes 

New Features
------------
* Allow Symfony 6 components
* Upgrade to PhpUnit 9.x

Other

Version 1.5.1
===========

New Feature
------------
* Allow annotations dev-master for a smoother transition to php 8 attributes


Version 1.5.0
===========

New Feature
------------
* Instantiate controllers through a PSR-11 container if provided.

Version 1.4.1
===========

Fixes
------------
* Clean up deprecated method usage
* Add missing type hints
* Clean up test namespace usage

Version 1.4
===========

New Features
------------
* Require Symfony 5 components
* Tested PHP 7.3 and 7.4, Require PHP 7.2
* Upgrade to PhpUnit 8.x

Version 1.3
===========

New Features
------------
* Allow Symfony 4 components
* Tested in PHP 7.2

Version 1.2
===========

New Features
------------
* Added additional before_response_sent event that gets fired even if an action was not invoked
* Added method to add multiple custom routes at once

Fixes
------
* Changed how routes are named to avoid collisions

Version 1.1
===========

New Features
------------
* Added ability to add custom routes via addRoute() and addCustomRoute() methods

Breaking changes
----------------
* Return 405 instead of 400 when method does not match
