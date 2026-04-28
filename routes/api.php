<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboradController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [UserController::class, 'register']);
Route::get('/suppliers', [SupplierController::class, 'index']);
Route::post('/suppliers', [SupplierController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Current User
    |--------------------------------------------------------------------------
    */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | Staff + Admin Access
    |--------------------------------------------------------------------------
    */

    // Category View
    Route::get('/categories', [CategoryController::class, 'getCategory']);

    // SubCategory View
    Route::get('/subcategories', [SubCategoryController::class, 'GetSub']);

    // Products View
    Route::get('/products', [ProductsController::class, 'GetProduct']);
    Route::get('/products/{id}', [ProductsController::class, 'GetProductById']);
    Route::get('/products/search/{keyword}', [ProductsController::class, 'SearchProduct']);
    Route::get('/products-low-stock', [ProductsController::class, 'LowStock']);
    Route::get('/products-expired', [ProductsController::class, 'expried']);

    /*
    |--------------------------------------------------------------------------
    | Admin Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->group(function () {

        // Admin Dashboard
        Route::get('/admin', function () {
            return response()->json([
                'message' => 'Welcome Admin'
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Category CRUD
        |--------------------------------------------------------------------------
        */
        Route::post('/categories', [CategoryController::class, 'AddCategory']);
        Route::put('/categories/{id}', [CategoryController::class, 'UpdateCategory']);
        Route::delete('/categories/{id}', [CategoryController::class, 'DeleteCategory']);

        /*
        |--------------------------------------------------------------------------
        | SubCategory CRUD
        |--------------------------------------------------------------------------
        */

        Route::get('/units', [UnitController::class, 'getUnits']);
        Route::post('/units', [UnitController::class, 'AddUnits']);



        Route::post('/subcategories', [SubCategoryController::class, 'AddSub']);
        Route::put('/subcategories/{id}', [SubCategoryController::class, 'UpdateSub']);
        Route::delete('/subcategories/{id}', [SubCategoryController::class, 'DeleteSub']);

        /*
        |--------------------------------------------------------------------------
        | Product CRUD
        |--------------------------------------------------------------------------
        */
        Route::post('/products', [ProductsController::class, 'AddProduct']);
        Route::put('/products/{id}', [ProductsController::class, 'UpdateProduct']);
        Route::delete('/products/{id}', [ProductsController::class, 'DeleteProducts']);
    });
});
