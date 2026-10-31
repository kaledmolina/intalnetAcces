<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rutas Públicas de Autenticación y Registro
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Rutas Protegidas por Autenticación (Usuario Logueado)
Route::middleware('auth')->group(function () {

    // Redirección inicial a Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Dashboard y Perfil
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/register-sede', [UserController::class, 'registerMySede'])->name('profile.register-sede');

    // Rutas protegidas contra edición por monitores (Solo Lectura)
    Route::middleware('readonly')->group(function () {
        Route::post('/dashboard/sync', [DashboardController::class, 'sync'])->name('dashboard.sync');
        Route::post('/dashboard/cloud-sync', [DashboardController::class, 'cloudSync'])->name('dashboard.cloud-sync');

        // Empleados, Captura de Huella, Confirmación, Sondeo y Importación ISAPI
        Route::post('/employees/{employee}/notes', [EmployeeController::class, 'saveNote'])->name('employees.notes.save');
        Route::post('/employees/capture-fingerprint', [EmployeeController::class, 'captureFingerprint'])->name('employees.capture-fingerprint');
        Route::post('/employees/confirm-fingerprint', [EmployeeController::class, 'confirmFingerprint'])->name('employees.confirm-fingerprint');
        Route::post('/employees/check-fingerprint-status', [EmployeeController::class, 'checkFingerprintStatus'])->name('employees.check-fingerprint-status');
        Route::post('/employees/import-from-devices', [EmployeeController::class, 'importFromDevices'])->name('employees.import');
        Route::resource('employees', EmployeeController::class)->except(['index', 'show']);

        // Departamentos
        Route::resource('departments', DepartmentController::class)->except(['create', 'show', 'edit', 'index']);
        Route::post('/departments/{department}/assign-employees', [DepartmentController::class, 'assignEmployees'])->name('departments.assign-employees');

        // Horarios
        Route::resource('schedules', ScheduleController::class)->except(['create', 'show', 'edit', 'index']);
        Route::post('/schedules/{schedule}/assign-department', [ScheduleController::class, 'assignDepartment'])->name('schedules.assign-department');

        // Marcaciones
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

        // Dispositivos / Huelleros
        Route::resource('devices', DeviceController::class)->except(['create', 'show', 'edit', 'index']);
        Route::post('/devices/{device}/test', [DeviceController::class, 'testConnection'])->name('devices.test');
        Route::post('/devices/{device}/sync', [DeviceController::class, 'syncDevice'])->name('devices.sync');
        Route::post('/devices/{device}/configure-auto', [DeviceController::class, 'configureAuto'])->name('devices.configure-auto');

        // Respaldos (Backup y Restauración)
        Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
    });

    // Rutas de lectura (Permitidas para Monitores)
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/export', [AttendanceController::class, 'exportCsv'])->name('attendance.export');
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::get('/backups/download', [BackupController::class, 'download'])->name('backups.download');

    // Descarga del Instalador de Escritorio para PCs en Red Local
    Route::get('/download-desktop', function () {
        if ($downloadUrl = env('DESKTOP_DOWNLOAD_URL')) {
            return redirect()->away($downloadUrl);
        }

        $publicPath = public_path('downloads/IntalnetAcces-1.0.0-setup.exe');
        if (file_exists($publicPath)) {
            return response()->download($publicPath, 'IntalnetAcces-1.0.0-setup.exe');
        }

        $distPath = base_path('nativephp/electron/dist/IntalnetAcces-1.0.0-setup.exe');
        if (file_exists($distPath)) {
            return response()->download($distPath, 'IntalnetAcces-1.0.0-setup.exe');
        }

        return redirect()->route('dashboard')->with('error', 'El instalador de escritorio aún no está disponible para descarga en este servidor. Contacte al administrador.');
    })->name('desktop.download');

    // Administración de Usuarios y Sedes (Solo Superadmin)
    Route::middleware('superadmin')->group(function () {
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::patch('/users/{user}/assign-sede', [UserController::class, 'assignSede'])->name('users.assign-sede');
        Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
        Route::resource('sedes', SedeController::class)->only(['store', 'update', 'destroy']);
    });
});
