<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Native\Desktop\Facades\Window;
use Native\Desktop\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\URL::forceScheme('http');

        // Si es una instalación nueva en cualquier PC limpia y no existen usuarios, sembrar datos iniciales
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && User::count() === 0) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            Log::warning('NativePHP auto-seed notice: ' . $e->getMessage());
        }

        Window::open()
            ->width(1366)
            ->height(850)
            ->minWidth(1024)
            ->minHeight(700)
            ->title('IntalnetAcces');
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
