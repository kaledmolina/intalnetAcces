<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\HikvisionService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::withCount('attendanceRecords')->get();
        return view('devices.index', compact('devices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:100',
            'port' => 'required|integer|min:1|max:65535',
            'protocol' => 'required|in:http,https',
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $device = Device::create($validated);

        return redirect()->route('devices.index')->with('success', "Dispositivo {$device->name} registrado.");
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:100',
            'port' => 'required|integer|min:1|max:65535',
            'protocol' => 'required|in:http,https',
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $device->update($validated);

        return redirect()->route('devices.index')->with('success', "Dispositivo {$device->name} actualizado.");
    }

    public function testConnection(Device $device, HikvisionService $hikvisionService)
    {
        $online = $hikvisionService->checkDeviceStatus($device);

        if ($online) {
            return redirect()->route('devices.index')->with('success', "Conexión exitosa con {$device->name} ({$device->ip_address}). Estado: ONLINE.");
        }

        return redirect()->route('devices.index')->with('error', "No se pudo conectar con {$device->name} ({$device->ip_address}). Verifica la red, IP o credenciales.");
    }

    public function syncDevice(Device $device, HikvisionService $hikvisionService)
    {
        $count = $hikvisionService->syncEventsFromDevice($device);

        if ($device->status === 'online') {
            return redirect()->route('devices.index')->with('success', "Dispositivo {$device->name} sincronizado. Se registraron {$count} marcaciones nuevas.");
        }

        return redirect()->route('devices.index')->with('error', "No se pudo sincronizar con {$device->name} porque está OFFLINE.");
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Dispositivo eliminado.');
    }

    public function configureAuto(Device $device, HikvisionService $hikvisionService)
    {
        $hikvisionService->configureAutoCheckInMode($device);
        return redirect()->route('devices.index')->with('success', "Configuración de Marcación Directa (Sin Teclas) enviada a {$device->name}.");
    }
}
