<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest: only for visitors who are not signed in
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| Signed in: every role, including tenants that are not approved yet
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/verification', [AuthController::class, 'verification'])->name('account.verification');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Tenant: signed in, role tenant, and approved by an admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:tenant', 'approved'])->group(function () {
    Route::view('/my-rents', 'tenant.rents.index')->name('my-rents');   // placeholder, replaced later
});

/*
|--------------------------------------------------------------------------
| Admin: signed in and role admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/garages', 'admin.garages.index')->name('garages.index');   // placeholder, replaced later
});
