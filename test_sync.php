<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$device = \App\Models\Device::find(2); // Oficina Principal
$service = new \App\Services\HikvisionService();
$start = now()->subDays(5);
$end = now()->endOfDay();
$count = $service->syncEventsFromDevice($device, $start, $end);
echo "Synced $count events\n";
