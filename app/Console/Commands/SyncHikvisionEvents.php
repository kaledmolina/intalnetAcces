<?php

namespace App\Console\Commands;

use App\Services\HikvisionService;
use Illuminate\Console\Command;

class SyncHikvisionEvents extends Command
{
    protected $signature = 'hikvision:sync {--days=7 : Cantidad de días hacia atrás a sincronizar}';
    protected $description = 'Sincroniza marcaciones de asistencia desde los relojes biométricos Hikvision mediante ISAPI';

    public function handle(HikvisionService $hikvisionService): int
    {
        $days = (int) $this->option('days');
        $this->info("Iniciando sincronización de marcaciones desde biométricos Hikvision (Últimos {$days} días)...");

        $results = $hikvisionService->syncAllDevices();

        foreach ($results as $deviceName => $data) {
            $statusText = strtoupper($data['status']);
            $count = $data['new_events'];
            $ip = $data['ip'];

            if ($data['status'] === 'online') {
                $this->info("✔ {$deviceName} ({$ip}) [{$statusText}]: {$count} nuevas marcaciones sincronizadas.");
            } else {
                $this->warn("✖ {$deviceName} ({$ip}) [{$statusText}]: No se pudo conectar.");
            }
        }

        $this->info("Proceso de sincronización completado.");

        return Command::SUCCESS;
    }
}
