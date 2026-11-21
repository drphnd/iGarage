<?php

use App\Http\Controllers\Admin\RenterController;
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
| Signed in: every role, including renters that are not approved yet
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/verification', [AuthController::class, 'verification'])->name('account.verification');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Renter: signed in, role renter, and approved by an admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:renter', 'approved'])->group(function () {
    Route::view('/my-rents', 'renter.rents.index')->name('my-rents');   // placeholder, replaced later
});

/*
|--------------------------------------------------------------------------
| Admin: signed in and role admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/garages', 'admin.garages.index')->name('garages.index');   // placeholder, replaced later

    // Renter verification
    Route::get('/renters', [RenterController::class, 'index'])->name('renters.index');
    Route::patch('/renters/{renter}/approve', [RenterController::class, 'approve'])->name('renters.approve');
    Route::patch('/renters/{renter}/reject', [RenterController::class, 'reject'])->name('renters.reject');
});
