<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User Login
Route::post('/v1/login', [UserController::class, 'login']);

// User Check Authentication
Route::post('/v1/checkAuth', [UserController::class, 'checkAuth'])->middleware('auth:sanctum');

// User Logout
Route::post('/v1/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

// User Change Password
Route::put('/v1/changePassword/{id}', [UserController::class, 'changePassword'])->middleware('auth:sanctum');

// User Lists
Route::get('/v1/user', [UserController::class, 'index'])->middleware('auth:sanctum');