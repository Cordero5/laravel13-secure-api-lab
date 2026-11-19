<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\HouseRuleController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\Tenant\AuthController as TenantAuthController;

Route::get('/', function () {
    return view('welcome');
});

// Sign-in screen. Only reachable while signed out.
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

Route::get('/tenant/login', [TenantAuthController::class, 'showLoginForm'])->name('tenant.login');
Route::post('/tenant/login', [TenantAuthController::class, 'login'])->name('tenant.login.submit');

Route::middleware(['auth', 'role:admin,tenant'])
    ->get('/house/rooms', [HouseController::class, 'rooms'])
    ->name('house.rooms');

Route::middleware(['auth', 'role:tenant'])
    ->prefix('tenant')
    ->name('tenant.')
    ->group(function () {
        Route::get('/dashboard', [TenantAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [TenantAuthController::class, 'logout'])->name('logout');
    });

// Every panel page and action is restricted on the server, not just at login.
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

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