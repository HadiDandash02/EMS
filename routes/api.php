<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

// Public: obtain a token (rate limited against brute force)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Everything else requires a valid Sanctum bearer token
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->whereNumber('task');
    Route::match(['put', 'patch'], '/tasks/{task}', [TaskController::class, 'update'])->whereNumber('task');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->whereNumber('task');

    // State transitions are explicit actions, never a free-form "status" field.
    Route::post('/tasks/{task}/start', [TaskController::class, 'start'])->whereNumber('task');
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->whereNumber('task');
    Route::post('/tasks/{task}/cancel', [TaskController::class, 'cancel'])->whereNumber('task');
    Route::post('/tasks/{task}/approve', [TaskController::class, 'approve'])->whereNumber('task');
    Route::post('/tasks/{task}/reject', [TaskController::class, 'reject'])->whereNumber('task');
});
