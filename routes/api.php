<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HistoryUserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TypesDocumentController;
use App\Http\Middleware\JwtMiddleware;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware([JwtMiddleware::class])->group(function () {
    Route::group([
        'prefix' => 'company',
    ], function ($router) {
        Route::get('/', [CompanyController::class, 'getAllCompanies']);
        Route::post('/', [CompanyController::class, 'createCompany']);
        Route::put('/', [CompanyController::class, 'updateCompany']);
    });

    Route::group([
        'prefix' => 'customer',
    ], function ($router) {
        Route::get('/', [CustomerController::class, 'getAllCustomers']);
        Route::post('/', [CustomerController::class, 'createCustomer']);
        Route::put('/', [CustomerController::class, 'updateCustomer']);
    });

    Route::group([
        'prefix' => 'user',
    ], function ($router) {
        Route::get('/', [UserController::class, 'getAllUsers']);
        Route::post('/', [UserController::class, 'createUser']);
        Route::put('/', [UserController::class, 'updateUser']);
    });

    Route::group([
        'prefix' => 'types_document',
    ], function ($router) {
        Route::get('/', [TypesDocumentController::class, 'getAllTypesDocument']);
    });
});


// Rutas para UserController
Route::get('/users',                          [UserController::class, 'index']);
Route::post('/users',                         [UserController::class, 'store']);
Route::get('/users/{id}',                     [UserController::class, 'show']);
Route::put('/users/{id}',                     [UserController::class, 'update']);
Route::delete('/users/{id}',                  [UserController::class, 'destroy']);

// Rutas para CompanyController
Route::get('/companies',                      [CompanyController::class, 'index']);
Route::post('/companies',                     [CompanyController::class, 'store']);
Route::get('/companies/{id}',                 [CompanyController::class, 'show']);
Route::put('/companies/{id}',                 [CompanyController::class, 'update']);
Route::delete('/companies/{id}',              [CompanyController::class, 'destroy']);

// Rutas para HistoryUserController
Route::get('/history-users',                  [HistoryUserController::class, 'index']);
Route::post('/history-users',                 [HistoryUserController::class, 'store']);
Route::get('/history-users/{id}',             [HistoryUserController::class, 'show']);
Route::put('/history-users/{id}',             [HistoryUserController::class, 'update']);
Route::delete('/history-users/{id}',          [HistoryUserController::class, 'destroy']);

Route::post('/upload-documents',                     [DocumentController::class, 'uploadDocument']);
