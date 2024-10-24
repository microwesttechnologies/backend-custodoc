<?php

use App\Http\Controllers\TypesDocumentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GlobalController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware([JwtMiddleware::class])->group(function () {
    Route::group([
        'prefix' => 'company',
    ], function () {
        Route::get('/', [CompanyController::class, 'getAllCompanies']);
        Route::post('/', [CompanyController::class, 'createCompany']);
        Route::put('/', [CompanyController::class, 'updateCompany']);
    });

    Route::group([
        'prefix' => 'customer',
    ], function () {
        Route::get('/', [CustomerController::class, 'getAllCustomers']);
        Route::post('/', [CustomerController::class, 'createCustomer']);
        Route::put('/', [CustomerController::class, 'updateCustomer']);
    });

    Route::group([
        'prefix' => 'user',
    ], function () {
        Route::get('/', [UserController::class, 'getAllUsers']);
        Route::post('/', [UserController::class, 'createUser']);
        Route::put('/', [UserController::class, 'updateUser']);
    });

    Route::group([
        'prefix' => 'types_document',
    ], function () {
        Route::get('/', [TypesDocumentController::class, 'getAllTypesDocument']);
    });

    Route::group([
        'prefix' => 'document',
    ], function () {
        Route::get('/', [DocumentController::class, 'getAllDocuments']);
        Route::post('/', [DocumentController::class, 'createDocument']);
    });

    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('getDetailCompany', [GlobalController::class, 'getDetailCompany']);
});
