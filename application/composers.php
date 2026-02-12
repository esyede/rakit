<?php

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| View Composer
|--------------------------------------------------------------------------
|
| Every time a view is created, the 'composer' event will be fired.
| You can listen to this event and use it to bind assets and data to views
| every time they are loaded.
|
| Generally, view composers are used to bind assets and data to views.
| For example, a view partial that displays a list of random blog posts.
| You can create nested view partials by loading them within your layout view.
| Then, register a composer for the partial view.
|
| <code>
|
|      // Register a view composer for the 'home' view:
|      View::composer('home', function ($view) {
|          $view->nest('footer', 'partials.footer');
|      });
|
|      // Register a view composer for the 'home' and 'profile' views:
|      View::composer(['home', 'profile'], function ($view) {
|          // ..
|      });
|
| </code>
|
*/

// ..
