<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'SIGEM API',
        'status' => 'online',
    ]);
});

Route::post('/register', [
    RegisteredUserController::class,
    'store',
])->name('register');

Route::post('/login', [
    AuthenticatedSessionController::class,
    'store',
])->name('login');

Route::post('/forgot-password', [
    PasswordResetLinkController::class,
    'store',
])
    ->middleware('throttle:3,1')
    ->name('password.email');

Route::post('/reset-password', [
    NewPasswordController::class,
    'store',
])
    ->middleware('throttle:5,1')
    ->name('password.update');

Route::post('/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])
    ->middleware('auth:sanctum')
    ->name('logout');
