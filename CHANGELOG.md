Version 1.2
===========

New Features
------------
* Added additional before_response_sent event that gets fired even if an action was not invoked
* Added method to add multiple custom routes at once


Version 1.1
===========

New Features
------------
* Added ability to add custom routes via addRoute() and addCustomRoute() methods

Breaking changes
----------------
* Return 405 instead of 400 when method does not match
