<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::post('/organizations/{organization}/refresh', [OrganizationController::class, 'refresh']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
    Route::get('/organizations/{organization}/reviews', [OrganizationController::class, 'reviews']);
});
