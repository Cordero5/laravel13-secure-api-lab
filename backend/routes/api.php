<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthorizationController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\HouseRuleController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public: mobile app authentication only.
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Everything else requires a Sanctum bearer token. Tenants get read access to
// house data and may update their own profile; admin writes stay on the web app.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [TenantController::class, 'me']);

    Route::apiResource('rooms', RoomController::class)->only(['index', 'show']);
    Route::apiResource('payments', PaymentController::class)->only(['index', 'show']);
    Route::apiResource('announcements', AnnouncementController::class)->only(['index', 'show']);
    Route::apiResource('tenants', TenantController::class)->only(['index', 'show', 'update']);
    Route::apiResource('check-ins', CheckInController::class)->only(['index', 'show']);
    Route::apiResource('house-rules', HouseRuleController::class)->only(['index', 'show']);
});

/*
|--------------------------------------------------------------------------
| Role-based authorization (RBAC laboratory)
|--------------------------------------------------------------------------
|
| Authentication (auth:sanctum) proves who the caller is; the dynamic "role"
| alias below decides whether that caller may enter. These routes are
| deliberately separate from the group above: the phone authenticates as a
| tenant, so guarding its read endpoints would blank five screens.
|
*/

// Admin only — a tenant token gets 403 here.
Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('/admin/overview', [AuthorizationController::class, 'overview']);

// Tenant only — an admin token gets 403 here.
Route::middleware(['auth:sanctum', 'role:tenant'])
    ->get('/tenant/summary', [AuthorizationController::class, 'summary']);

// Both roles — the equivalent of the lab's shared /students route.
Route::middleware(['auth:sanctum', 'role:admin,tenant'])
    ->get('/house/rooms', [AuthorizationController::class, 'rooms']);

/*
|--------------------------------------------------------------------------
| Admin-only write endpoints
|--------------------------------------------------------------------------
|
| The room, tenant and payment records were previously editable only by signing
| into the web panel. These routes expose the same create/update/delete actions
| over JSON, guarded by the dynamic role middleware, so a tenant bearer token is refused
| with 403 before the controller runs and nothing is written.
|
| PUT /api/tenants/{id} is intentionally absent: it already lives in the
| sanctum group above, where it lets a boarder edit their own contact details.
| Registering the same path and method twice would shadow that route.
|
*/
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('rooms', RoomController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('tenants', TenantController::class)->only(['store', 'destroy']);
    Route::apiResource('payments', PaymentController::class)->only(['store', 'update', 'destroy']);
});