<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantController;
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

// Tenant

// Tenant Lists
Route::get('/v1/tenant/index', [TenantController::class, 'index'])->middleware('auth:sanctum');

// Tenant Store New Data
Route::post('/v1/tenant/store', [TenantController::class, 'store'])->middleware('auth:sanctum');

// Tenant Update Data
Route::put('/v1/tenant/update/{id}', [TenantController::class, 'update'])->middleware('auth:sanctum');

// Tenant Delete Data
Route::delete('/v1/tenant/destroy/{id}', [TenantController::class, 'destroy'])->middleware('auth:sanctum');

// Tenant Delete Datas
Route::post('/v1/tenant/destroys', [TenantController::class, 'destroys'])->middleware('auth:sanctum');