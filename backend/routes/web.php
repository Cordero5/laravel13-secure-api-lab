<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\HouseRuleController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Sign-in screen. Only reachable while signed out.
Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');

// Everything below requires a signed-in admin. Authenticate sends guests to
// the login page above, and AuthController only accepts role = admin.
Route::middleware('auth')->group(function () {
    Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        Route::get('/checkins', [CheckInController::class, 'index'])->name('checkins.index');
        Route::post('/checkins', [CheckInController::class, 'store'])->name('checkins.store');
        Route::put('/checkins/{checkIn}', [CheckInController::class, 'update'])->name('checkins.update');
        Route::patch('/checkins/{checkIn}/checkout', [CheckInController::class, 'checkout'])->name('checkins.checkout');
        Route::delete('/checkins/{checkIn}', [CheckInController::class, 'destroy'])->name('checkins.destroy');

        Route::get('/rules', [HouseRuleController::class, 'index'])->name('rules.index');
        Route::post('/rules', [HouseRuleController::class, 'store'])->name('rules.store');
        Route::put('/rules/{houseRule}', [HouseRuleController::class, 'update'])->name('rules.update');
        Route::delete('/rules/{houseRule}', [HouseRuleController::class, 'destroy'])->name('rules.destroy');

        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });
});
