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
    Route::post('/dashboard/sync', [DashboardController::class, 'sync'])->name('dashboard.sync');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/register-sede', [UserController::class, 'registerMySede'])->name('profile.register-sede');

    // Empleados, Captura de Huella, Confirmación, Sondeo y Importación ISAPI
    Route::post('/employees/capture-fingerprint', [EmployeeController::class, 'captureFingerprint'])->name('employees.capture-fingerprint');
    Route::post('/employees/confirm-fingerprint', [EmployeeController::class, 'confirmFingerprint'])->name('employees.confirm-fingerprint');
    Route::post('/employees/check-fingerprint-status', [EmployeeController::class, 'checkFingerprintStatus'])->name('employees.check-fingerprint-status');
    Route::post('/employees/import-from-devices', [EmployeeController::class, 'importFromDevices'])->name('employees.import');
    Route::resource('employees', EmployeeController::class);

    // Departamentos
    Route::resource('departments', DepartmentController::class)->except(['create', 'show', 'edit']);
    Route::post('/departments/{department}/assign-employees', [DepartmentController::class, 'assignEmployees'])->name('departments.assign-employees');

    // Horarios
    Route::resource('schedules', ScheduleController::class)->except(['create', 'show', 'edit']);
    Route::post('/schedules/{schedule}/assign-department', [ScheduleController::class, 'assignDepartment'])->name('schedules.assign-department');

    // Marcaciones y Reportes
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/export', [AttendanceController::class, 'exportCsv'])->name('attendance.export');

    // Dispositivos / Huelleros
    Route::resource('devices', DeviceController::class)->except(['create', 'show', 'edit']);
    Route::post('/devices/{device}/test', [DeviceController::class, 'testConnection'])->name('devices.test');
    Route::post('/devices/{device}/sync', [DeviceController::class, 'syncDevice'])->name('devices.sync');
    Route::post('/devices/{device}/configure-auto', [DeviceController::class, 'configureAuto'])->name('devices.configure-auto');

    // Respaldos (Backup y Restauración)
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::get('/backups/download', [BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');

    // Administración de Usuarios y Sedes (Solo Superadmin)
    Route::middleware('superadmin')->group(function () {
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::patch('/users/{user}/assign-sede', [UserController::class, 'assignSede'])->name('users.assign-sede');
        Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
        Route::resource('sedes', SedeController::class)->only(['store', 'update', 'destroy']);
    });
});
