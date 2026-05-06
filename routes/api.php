<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboradController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\WarehouseController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


Route::get('/orders/today', [OrderController::class, 'todaySales']);
Route::get('/orders/staff', [OrderController::class, 'staffSales']);
Route::get('/orders/all', [OrderController::class, 'allSales']);

Route::get('/customers', [CustomerController::class, 'index']);
Route::get('/customers/phone/{phone}', [CustomerController::class, 'getByPhone']);
Route::get('/customers/stats', [CustomerController::class, 'stats']);
Route::get('/customers/{id}', [CustomerController::class, 'show']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/products/dashboard', [DashboradController::class, 'productDashboard']);
Route::get('/purchases/pending', [PurchaseController::class, 'getPendingPurchases']);
Route::get('/purchase-payments', [PurchaseController::class, 'getAllPayments']);
Route::get('/purchases', [PurchaseController::class, 'index']);
Route::post('/purchases', [PurchaseController::class, 'store']);
Route::get('/purchases/{id}', [PurchaseController::class, 'show']);
Route::delete('/purchases/{id}', [PurchaseController::class, 'destroy']);

Route::get('/purchases/{id}/payments', [PurchaseController::class, 'getPayments']);
Route::post('/purchases/{id}/payments', [PurchaseController::class, 'addPayment']);
Route::delete('/purchases/{purchaseId}/payments/{paymentId}', [PurchaseController::class, 'deletePayment']);

Route::get('/warehouses', [WarehouseController::class, 'index']);
Route::post('/warehouses', [WarehouseController::class, 'store']);
Route::put('/warehouses/{id}', [WarehouseController::class, 'update']);
Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy']);

Route::post('/register', [UserController::class, 'register']);
Route::get('/suppliers/dashboard', [SupplierController::class, 'dashboard']);
Route::get('/suppliers', [SupplierController::class, 'index']);
Route::post('/suppliers', [SupplierController::class, 'store']);
Route::post('/logout', [UserController::class, 'logout']);
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
    | Staff + Admin Access (VIEW ONLY)
    |--------------------------------------------------------------------------
    */

    //  FIXED: ONLY KEEP ONE products route HERE
    Route::get('/products', [ProductsController::class, 'GetProduct']);
    Route::get('/products/{id}', [ProductsController::class, 'GetProductById']);
    Route::get('/products/search/{keyword}', [ProductsController::class, 'SearchProduct']);
    Route::get('/products-low-stock', [ProductsController::class, 'LowStock']);
    Route::get('/products-expired', [ProductsController::class, 'expried']);

    // Category View
    Route::get('/categories', [CategoryController::class, 'getCategory']);
     Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/profile/password', [UserController::class, 'updatePassword']);
    Route::post('/profile/avatar', [UserController::class, 'uploadAvatar']);
    Route::get('/staff/profile', [UserController::class, 'getStaffDetail']);
    // SubCategory View
    Route::get('/subcategories', [SubCategoryController::class, 'GetSub']);

    /*
    |--------------------------------------------------------------------------
    | Admin Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->group(function () {

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
        Route::post('/subcategories', [SubCategoryController::class, 'AddSub']);
        Route::put('/subcategories/{id}', [SubCategoryController::class, 'UpdateSub']);
        Route::delete('/subcategories/{id}', [SubCategoryController::class, 'DeleteSub']);

        /*
        |--------------------------------------------------------------------------
        | Units
        |--------------------------------------------------------------------------
        */
        Route::get('/units', [UnitController::class, 'getUnits']);
        Route::post('/units', [UnitController::class, 'AddUnits']);
        // routes/api.php
        Route::put('/staff/{id}/salary-status', [StaffController::class, 'updateSalaryStatus']);
        Route::get('/staff/{id}/monthly-salaries', [StaffController::class, 'getMonthlySalaries']);
        /*
        |--------------------------------------------------------------------------
        | Orders (Admin)
        |--------------------------------------------------------------------------
        */
        Route::get('/orders/all', [OrderController::class, 'allSales']);
        Route::get('/orders/stats', [OrderController::class, 'salesStats']);
        Route::get('/orders/{id}', [OrderController::class, 'showOrder']);


        Route::get('/positions', [PositionController::class, 'index']);
        Route::post('/positions', [PositionController::class, 'store']);
        Route::put('/positions/{id}', [PositionController::class, 'update']);
        Route::delete('/positions/{id}', [PositionController::class, 'destroy']);
        Route::get('/staff', [StaffController::class, 'index']);
        Route::get('/staff/{id}', [StaffController::class, 'show']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::put('/staff/{id}', [StaffController::class, 'update']);
        Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
            Route::put('/staff/{id}/salary-status', [StaffController::class, 'updateSalaryStatus']);

        /*
        |--------------------------------------------------------------------------
        | Product CRUD (ADMIN ONLY)
        |--------------------------------------------------------------------------
        */
        //  IMPORTANT: REMOVE GET /products HERE
        Route::post('/products', [ProductsController::class, 'AddProduct']);
        Route::put('/products/{id}', [ProductsController::class, 'UpdateProduct']);
        Route::delete('/products/{id}', [ProductsController::class, 'DeleteProducts']);

        Route::prefix('expenses')->group(function () {

            Route::get('/', [ExpenseController::class, 'index']);

            Route::post('/', [ExpenseController::class, 'store']);

            Route::put('/{id}', [ExpenseController::class, 'update']);

            Route::delete('/{id}', [ExpenseController::class, 'destroy']);

            Route::get('/stats', [ExpenseController::class, 'stats']);
        });
    });
});
