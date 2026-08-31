<?php

defined('DS') or exit('No direct access.');

use System\View;

/*
|--------------------------------------------------------------------------
| View Composer
|--------------------------------------------------------------------------
|
| Every time a view is created, its 'composer' event will be executed.
| You can listen to this event and use it to bind assets
| and data to the view each time it is loaded.
|
| A common use of this feature is a partial sidebar navigation view
| that displays a random list of blog posts. You can create
| nested partial views by loading them within a layout view.
| Then, register a composer for that partial view.
|
| <code>
|
|      // Register a view composer for the "home" view:
|      View::composer('home', function ($view) {
|          $view->nest('footer', 'partials.footer');
|      });
|
|      // Register a composer that handles multiple views:
|      View::composer(['home', 'profile'], function ($view) {
|          // ..
|      });
|
| </code>
|
*/

// ..
