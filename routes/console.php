<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('vectis:poll-gazette --parse')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
