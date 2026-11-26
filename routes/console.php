<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sincroniza las marcaciones de los biométricos cada minuto, sin depender de
// que alguien abra el panel web o pulse "Sincronizar Marcaciones".
Schedule::command('hikvision:sync')->everyMinute()->withoutOverlapping();
