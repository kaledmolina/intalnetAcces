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

// Mantiene acotadas las tablas del OAuth del MCP: códigos y refresh tokens
// caducados, y clientes que se registraron pero nunca llegaron a usarse.
Schedule::command('mcp:prune-oauth')->dailyAt('03:30')->withoutOverlapping();
