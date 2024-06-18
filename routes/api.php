<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User

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

// Room

// Room Lists
Route::get('/v1/room/index', [RoomController::class, 'index'])->middleware('auth:sanctum');

// Room Store New Data
Route::post('/v1/room/store', [RoomController::class, 'store'])->middleware('auth:sanctum');

// Room Update Data
Route::put('/v1/room/update/{id}', [RoomController::class, 'update'])->middleware('auth:sanctum');

// Room Delete Data
Route::put('/v1/room/destroy/{id}', [RoomController::class, 'destroy'])->middleware('auth:sanctum');

// Room Upload Image
Route::post('/v1/uploadImage', [RoomController::class, 'uploadImage'])->middleware('auth:sanctum');