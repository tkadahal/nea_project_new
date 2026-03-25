<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Console Routes Scheduler
|--------------------------------------------------------------------------
*/

app(Schedule::class)->command('model:prune')->hourly();
