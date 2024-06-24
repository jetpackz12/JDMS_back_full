<?php

use App\Http\Controllers\ElectricityBillingPaymentController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantBillingPaymentController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaterBillingPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User

// User Login
Route::post('/v1/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('v1/user')->group(function () {
    // User Check Authentication
    Route::post('/checkAuth', [UserController::class, 'checkAuth']);
    // User Logout
    Route::post('/logout', [UserController::class, 'logout']);
    // User Change Password
    Route::put('/changePassword/{id}', [UserController::class, 'changePassword']);
    // User Lists
    Route::get('/user', [UserController::class, 'index']);
});

// Home
Route::middleware('auth:sanctum')->prefix('v1/home')->group(function () {
    // Home Data
    Route::get('/index', [HomeController::class, 'index']);
});

// Room
Route::middleware('auth:sanctum')->prefix('v1/room')->group(function () {
    // Room Lists
    Route::get('/index', [RoomController::class, 'index']);
    // Room Store New Data
    Route::post('/store', [RoomController::class, 'store']);
    // Room Update Data
    Route::put('/update/{id}', [RoomController::class, 'update']);
    // Room Delete Data
    Route::put('/destroy/{id}', [RoomController::class, 'destroy']);
    // Room Upload Image
    Route::post('/uploadImage', [RoomController::class, 'uploadImage']);
});

// Tenant
Route::middleware('auth:sanctum')->prefix('v1/tenant')->group(function () {
    // Tenant Lists
    Route::get('/index', [TenantController::class, 'index']);
    // Tenant Store New Data
    Route::post('/store', [TenantController::class, 'store']);
    // Tenant Update Data
    Route::put('/update/{id}', [TenantController::class, 'update']);
    // Tenant Delete Data
    Route::delete('/destroy/{id}', [TenantController::class, 'destroy']);
    // Tenant Delete Datas
    Route::post('/destroys', [TenantController::class, 'destroys']);
});

// Guest
Route::middleware('auth:sanctum')->prefix('v1/guest')->group(function () {
    // Guest Lists
    Route::get('/index', [GuestController::class, 'index']);
    // Guest Store New Data
    Route::post('/store', [GuestController::class, 'store']);
    // Guest Update Data
    Route::put('/update/{id}', [GuestController::class, 'update']);
    // Guest Delete Data
    Route::delete('/destroy/{id}', [GuestController::class, 'destroy']);
    // Guest Delete Datas
    Route::post('/destroys', [GuestController::class, 'destroys']);
});

// Water Billing Payment
Route::middleware('auth:sanctum')->prefix('v1/waterBillingPayment')->group(function () {
    // Water Billing Payment Lists
    Route::get('/index', [WaterBillingPaymentController::class, 'index']);
    // Water Billing Payment Store New Data
    Route::post('/store', [WaterBillingPaymentController::class, 'store']);
    // Water Billing Payment Update Data
    Route::put('/update/{id}', [WaterBillingPaymentController::class, 'update']);
    // Water Billing Payment Date Filter
    Route::post('/dateFilter', [WaterBillingPaymentController::class, 'dateFilter']);
});

// Electricity Billing Payment
Route::middleware('auth:sanctum')->prefix('v1/electricityBillingPayment')->group(function () {
    // Electricity Billing Payment Lists
    Route::get('/index', [ElectricityBillingPaymentController::class, 'index']);
    // Electricity Billing Payment Store New Data
    Route::post('/store', [ElectricityBillingPaymentController::class, 'store']);
    // Electricity Billing Payment Update Data
    Route::put('/update/{id}', [ElectricityBillingPaymentController::class, 'update']);
    // Electricity Billing Payment Date Filter
    Route::post('/dateFilter', [ElectricityBillingPaymentController::class, 'dateFilter']);
});

// Tenent Billing Payment
Route::middleware('auth:sanctum')->prefix('v1/tenantBillingPayment')->group(function () {
    // Tenent Billing Payment Lists
    Route::get('/index', [TenantBillingPaymentController::class, 'index']);
    // Tenent Billing Payment Update Status
    Route::put('/updateStatus/{id}', [TenantBillingPaymentController::class, 'updateStatus']);
    // Tenent Billing Payment Date Filter
    Route::post('/dateFilter', [TenantBillingPaymentController::class, 'dateFilter']);
});

// Reports
Route::middleware('auth:sanctum')->prefix('v1/report')->group(function () {
    // Reports Lists
    Route::get('/index', [ReportController::class, 'index']);
    // Reports Date Filter
    Route::post('/dateFilter', [ReportController::class, 'dateFilter']);
});
