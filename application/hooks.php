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

// Scheduled events, run by 'php rakit schedule:run' from a cron entry that fires every minute:
// * * * * * cd /path/to/app && php rakit schedule:run >> /dev/null 2>&1
//
// Hook::listen('rakit.schedule', function () {
//     Schedule::command('job:runall')->every_minute()->without_overlapping();
//     Schedule::call(function () {
//         DB::table('password_resets')->where('created_at', '<', now()->subDays(1))->delete();
//     })->daily()->name('prune password resets');
// });
