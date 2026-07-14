<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
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

Route::post('/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])
    ->middleware('auth:sanctum')
    ->name('logout');
