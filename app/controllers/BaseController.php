
2. BaseController.php

This is a parent class for all your controllers (like DiscoverController, LoginController, etc.).

Its purpose is to provide common functionality that all controllers might need, so you don’t repeat code.

Typical things in BaseController:

Loading views/templates.

Redirecting to another page.

Sharing common data with views.

Authentication checks or helper methods.

Example in PHP: