<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/login', [UserController::class, 'login']);

Route::post('/v1/checkAuth', [UserController::class, 'checkAuth'])->middleware('auth:sanctum');

Route::get('/v1/user', [UserController::class, 'index'])->middleware('auth:sanctum');