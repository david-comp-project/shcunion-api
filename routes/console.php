<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


if (app()->environment('production')) {
    Schedule::command('app:project-notification-command')->daily();
    Schedule::command('app:user-status-command')->daily();
}
