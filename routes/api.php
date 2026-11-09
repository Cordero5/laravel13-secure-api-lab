<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return response()->json([
        'message' => 'Welcome to the Laravel 13 API',
    ]);
});

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // RBAC Protected Routes
    Route::get('/students', function () {
        return response()->json([
            'students' => [
                ['id' => 1, 'name' => 'ALFRED CORDERO'],
                ['id' => 2, 'name' => 'PRINCESS PERUDA']
            ]
        ]);
    })->middleware('role:admin,student');

    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Welcome Admin!']);
    })->middleware('admin');

    Route::get('/student/dashboard', function () {
        return response()->json(['message' => 'Welcome Student!']);
    })->middleware('role:student');
});