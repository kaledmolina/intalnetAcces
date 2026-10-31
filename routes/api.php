<?php

use App\Http\Controllers\Api\CloudSyncController;
use App\Http\Controllers\Api\DesktopAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/desktop')->group(function () {
    Route::post('/verify-login', [DesktopAuthController::class, 'verify']);
    Route::post('/sync-push', [CloudSyncController::class, 'push']);
    Route::post('/sync-pull', [CloudSyncController::class, 'pull']);
});
