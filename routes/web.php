<?php

use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\RentExtendController;
use App\Http\Controllers\Admin\RenterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EvChargeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RentController;
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
    Route::get('/my-rents', [RentController::class, 'index'])->name('my-rents');
    Route::get('/rents/create', [RentController::class, 'create'])->name('rents.create');
    Route::post('/rents', [RentController::class, 'store'])->name('rents.store');

    // Extra charging session for an electric car during an active rent
    Route::get('/rents/{rent}/charges/create', [EvChargeController::class, 'create'])->name('rents.charges.create');
    Route::post('/rents/{rent}/charges', [EvChargeController::class, 'store'])->name('rents.charges.store');
});

/*
|--------------------------------------------------------------------------
| Admin: signed in and role admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Garages: list with today's status, and bulk create (no edit or delete, not in the case study)
    Route::get('/garages', [GarageController::class, 'index'])->name('garages.index');
    Route::get('/garages/create', [GarageController::class, 'create'])->name('garages.create');
    Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');

    // Renter verification
    Route::get('/renters', [RenterController::class, 'index'])->name('renters.index');
    Route::patch('/renters/{renter}/approve', [RenterController::class, 'approve'])->name('renters.approve');
    Route::patch('/renters/{renter}/reject', [RenterController::class, 'reject'])->name('renters.reject');

    // Rent extensions (admin only, rents active today)
    Route::get('/extends', [RentExtendController::class, 'index'])->name('extends.index');
    Route::get('/rents/{rent}/extend', [RentExtendController::class, 'create'])->name('extends.create');
    Route::post('/rents/{rent}/extend', [RentExtendController::class, 'store'])->name('extends.store');
});
