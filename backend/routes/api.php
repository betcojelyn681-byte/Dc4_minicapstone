<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);

    Route::middleware('role:admin')->group(function () {
        foreach (['courses', 'subjects', 'sections', 'rooms'] as $resource) {
            Route::get("/$resource", [ResourceController::class, 'index'])->name("$resource.index");
            Route::post("/$resource", [ResourceController::class, 'store'])->name("$resource.store");
            Route::get("/$resource/{id}", [ResourceController::class, 'show'])->name("$resource.show");
            Route::put("/$resource/{id}", [ResourceController::class, 'update'])->name("$resource.update");
            Route::delete("/$resource/{id}", [ResourceController::class, 'destroy'])->name("$resource.destroy");
        }
        Route::post('/schedules', [ScheduleController::class, 'store']);
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update']);
        Route::patch('/schedules/{schedule}/status', [ScheduleController::class, 'updateStatus']);
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy']);
        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
    });
});
