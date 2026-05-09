<?php

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Hook Handlers
|--------------------------------------------------------------------------
|
| Hooks are Rakit's event-listener system. They let you decouple resources
| so classes, libraries, and plugins do not mix and stay easy to monitor.
| Register a callback with Hook::listen() and the framework will run it
| whenever the matching event is fired.
|
*/

Hook::listen('404', function () {
    return Response::error(404);
});

Hook::listen('500', function () {
    return Response::error(500);
});
