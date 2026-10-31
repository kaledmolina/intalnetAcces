<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Schedule;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Sede y Usuario Administrador Inicial (para primer arranque en PC nueva)
        $sede = Sede::firstOrCreate(
            ['code' => 'SEDE-001'],
            ['name' => 'Sede Principal']
        );

        if (User::where('email', 'admin@intalnet.com')->doesntExist() && User::where('email', 'kaledmoly@gmail.com')->doesntExist()) {
            User::create([
                'name' => 'INTALNET Admin',
                'email' => 'admin@intalnet.com',
                'password' => Hash::make('admin1234'),
                'is_superadmin' => true,
                'is_active' => true,
                'sede_id' => $sede->id,
                'sede' => $sede->name,
            ]);
        }

        // 2. Configuración de los 2 Huelleros Hikvision ISAPI del cliente
        if (Device::count() === 0) {
            Device::create([
                'name' => 'Huellero Cabecera',
                'ip_address' => '192.168.252.10',
                'port' => 443,
                'protocol' => 'https',
                'username' => 'admin',
                'password' => 'Colombia2026**',
                'location' => 'Entrada Principal / Cabecera',
                'status' => 'unknown',
            ]);

            Device::create([
                'name' => 'Huellero Secundario',
                'ip_address' => '192.168.251.10',
                'port' => 443,
                'protocol' => 'https',
                'username' => 'admin',
                'password' => 'Colombia2026**',
                'location' => 'Entrada Auxiliar',
                'status' => 'unknown',
            ]);
        }

        // 3. Horario Predeterminado
        if (Schedule::count() === 0) {
            $schedule = Schedule::create([
                'name' => 'Horario General (8:00 AM - 5:00 PM)',
                'tolerance_minutes' => 15,
                'is_default' => true,
            ]);

            for ($i = 1; $i <= 7; $i++) {
                $schedule->days()->create([
                    'day_of_week' => $i,
                    'is_working_day' => true,
                    'entry_time' => '08:00:00',
                    'exit_time' => '17:00:00',
                ]);
            }
        }
    }
}
