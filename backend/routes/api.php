<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Manifestation\ManifestationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/manifestations', [
        ManifestationController::class,
        'store',
    ])->name('manifestations.store');

    Route::prefix('admin')
        ->middleware('admin')
        ->group(function (): void {
            Route::get('/users', [
                UserController::class,
                'index',
            ])->name('admin.users.index');

            Route::patch('/users/{user}', [
                UserController::class,
                'update',
            ])->name('admin.users.update');

            Route::get('/audit-logs', [
                AuditLogController::class,
                'index',
            ])->name('admin.audit-logs.index');
        });
});
