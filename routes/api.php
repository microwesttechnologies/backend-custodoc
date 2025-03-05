<?php

use App\Http\Controllers\TypesDocumentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GlobalController;
use App\Http\Controllers\RoutesController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AreaController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogRequest;

Route::post('/validateIfTokenIsValid', [AuthController::class, 'validateIfTokenIsValid']);
Route::post('/sendLinkResetPassword', [AuthController::class, 'sendLinkResetPassword']);
Route::post('/resetPassword', [AuthController::class, 'resetPassword']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware([JwtMiddleware::class, LogRequest::class])->group(function () {
    Route::group([
        'prefix' => 'company',
    ], function () {
        Route::delete('/{id_company}', [CompanyController::class, 'deleteCompany']);
        Route::get('/', [CompanyController::class, 'getAllCompanies']);
        Route::post('/', [CompanyController::class, 'createCompany']);
        Route::put('/', [CompanyController::class, 'updateCompany']);
    });

    Route::group([
        'prefix' => 'customer',
    ], function () {
        Route::delete('/{identification}', [CustomerController::class, 'deleteCustomer']);
        Route::get('/{id_company?}', [CustomerController::class, 'getAllCustomers']);
        Route::post('/', [CustomerController::class, 'createCustomer']);
        Route::put('/', [CustomerController::class, 'updateCustomer']);
    });

    Route::group([
        'prefix' => 'user',
    ], function () {
        Route::get('/getAllRankingUsers', [UserController::class, 'getAllRankingUsers']);
        Route::delete('/{identification}', [UserController::class, 'deleteUser']);
        Route::get('/getUserProfile', [UserController::class, 'getUserProfile']);
        Route::put('/updatePassword', [UserController::class, 'updatePassword']);
        Route::get('/{id_company?}', [UserController::class, 'getAllUsers']);
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
        Route::get('/getAllDocumentsByCustomer/{id_customer}', [DocumentController::class, 'getAllDocumentsByCustomer']);
        Route::get('/getDocumentsByFolder/{id_folder}', [DocumentController::class, 'getDocumentsByFolder']);
        Route::get('/markAndDesmarkFavorite', [DocumentController::class, 'markAndDesmarkFavorite']);
        Route::get('/restoreDocument/{id_history}', [DocumentController::class, 'restoreDocument']);
        Route::post('/bulkUploadDocuments', [DocumentController::class, 'bulkUploadDocuments']);
        Route::delete('/{id_history}', [DocumentController::class, 'deleteDocument']);
        Route::get('/getFile/{id_history}', [DocumentController::class, 'getFile']);
        Route::post('/', [DocumentController::class, 'createOrUpdateDocument']);
        Route::get('/', [DocumentController::class, 'getAllDocuments']);
    });

    Route::group([
        'prefix' => 'folder',
    ], function () {
        Route::post('/deleteFoldersAndDocumentsById', [FolderController::class, 'deleteFoldersAndDocumentsById']);
        Route::get('/getFoldersByParent/{parent}', [FolderController::class, 'getFoldersByParent']);
        Route::get('/restoreFolder/{id_folder}', [FolderController::class, 'restoreFolder']);
        Route::delete('/{id_folder}', [FolderController::class, 'deleteFolder']);
        Route::post('/', [FolderController::class, 'createFolder']);
        Route::put('/', [FolderController::class, 'updateFolder']);
    });

    Route::group([
        'prefix' => 'routes',
    ], function () {
        Route::get('/getRoutesAndPermissions', [RoutesController::class, 'getRoutesAndPermissions']);
        Route::get('/getRoutesByRole', [RoutesController::class, 'getRoutesByRole']);
    });

    Route::group([
        'prefix' => 'roles',
    ], function () {
        Route::get('/getRolesByCompany/{id_company?}', [RolesController::class, 'getRolesByCompany']);
        Route::get('/getRolesAndPermissions', [RolesController::class, 'getRolesAndPermissions']);
        Route::post('/', [RolesController::class, 'createRol']);
        Route::put('/', [RolesController::class, 'updateRol']);
    });

    Route::group([
        'prefix' => 'areas',
    ], function () {
        Route::get('/{id_company?}', [AreaController::class, 'getAllAreas']);
        Route::post('/', [AreaController::class, 'createArea']);
        Route::put('/', [AreaController::class, 'updateArea']);
    });

    Route::get('getDetailCompany', [GlobalController::class, 'getDetailCompany']);
    Route::get('logout', [AuthController::class, 'logout']);
});

Route::get('/test-time', function () {
    return response()->json([
        'now' => now()->toDateTimeString(),
        'timezone' => config('app.timezone'),
    ]);
});
