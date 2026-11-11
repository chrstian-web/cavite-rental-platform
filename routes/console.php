<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Owners whose paid plan has ended fall back to Free (and extra listings are hidden).
Illuminate\Support\Facades\Schedule::command('subscriptions:expire')->dailyAt('00:10');
