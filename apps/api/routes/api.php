<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyOwnersController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FavoriteProductsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\ListItensController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UnityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::post('/users/revertDeleted/{user}', [UserController::class, 'revertDestroy'])->withTrashed();
        Route::get('/users/export', [UserController::class, 'export']);
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        Route::apiResource('/companies', CompanyController::class);
        Route::post('/products/import', [ProductController::class, 'import']);
        Route::get('/products/export', [ProductController::class, 'export']);
        Route::post('/products/bulk-validate', [ProductController::class, 'bulkValidate']);
        Route::apiResource('/products', ProductController::class);
        Route::apiResource('/users', UserController::class)->withTrashed(['show', 'update', 'destroy']);
        Route::apiResource('/categories', ProductCategoryController::class);
    });
});


// Rotas de verificação de email
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('api.verification.verify');

Route::get('/email/verify', [AuthController::class, 'sendVerificationNotice'])
    ->middleware(['auth:sanctum'])
    ->name('verification.notice');

Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.resend');
Route::put('/listItems/{list}', [ListItensController::class, 'update']);

// Rotas autenticadas
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    // ADDRESS
    Route::apiResource('/addresses', AddressController::class);

    Route::apiResource('lists', ListController::class);
    Route::post('/lists/{list}/optimize', [ListController::class, 'optimize']);

    // Route::apiResource('/listItems', ListItensController::class);

    // PRODUCTS
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // UNITIES
    Route::apiResource('/unities', UnityController::class);
    //FAVORITE-PRODUCTS
    Route::post('/products/{product}/favorite', [FavoriteProductsController::class, 'favorite']);

    // CATEGORIES
    Route::get('/categories', [ProductCategoryController::class, 'index']);

    // INVOICES
    Route::post('/invoice/process', [InvoiceController::class, 'processInvoice']);
    Route::post('/invoice/processXML', [InvoiceController::class, 'processXML']);

    //EVENTS
    Route::apiResource('/events', EventController::class);
    Route::post('/events/check-all', [EventController::class, 'checkAll']);

    // COMPANIES
    Route::apiResource('/companies', CompanyController::class);
    Route::get('/companies/{company}/dashboard', [CompanyController::class, 'dashboardData']);
    Route::post('/companies/submit', [CompanyController::class, 'submit']);

    //COMPANY OWNERS
    Route::post('companies/{company}/request-access', [CompanyOwnersController::class, 'requestAccess']);
    Route::post('companies/request-with-new-company', [CompanyOwnersController::class, 'storeCompanyAndRequest']);
    Route::get('user/company-requests', [CompanyOwnersController::class, 'index']);

    Route::get('/dashboard-data', [UserController::class, 'dashboardData']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
});
