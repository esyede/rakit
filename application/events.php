<?php

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Events Handler
|--------------------------------------------------------------------------
|
| Event handler gave you a way to decouple resources in your application,
| so that classes, libraries, or plugins do not mix and are easier to monitor.
| It allows you to listen to specific events and execute code when those events occur.
|
*/

Event::listen('404', function () {
    return Response::error(404);
});

Event::listen('500', function () {
    return Response::error(500);
});
