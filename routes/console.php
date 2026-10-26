<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('geo:poll-gazette --parse')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
