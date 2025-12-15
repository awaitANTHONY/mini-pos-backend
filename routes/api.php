<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;

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

Route::group(['middleware' => ['x_check'], 'prefix' => 'v1'], function ()
{
    //Api Controller
    Route::get('settings', [Controllers\Api\v1\ApiController::class, 'settings']);
    // Categories
    Route::get('categories', [Controllers\Api\v1\PosController::class, 'categoriesList']);
    // Items
    Route::get('items', [Controllers\Api\v1\PosController::class, 'itemsList']);
    // Ingredients
    Route::get('ingredients', [Controllers\Api\v1\PosController::class, 'ingredientsList']);

    Route::post('signin', [Controllers\Api\v1\AuthController::class, 'signin']);

    Route::group(['middleware' => 'auth:sanctum'], function ()
    {
        //User
        Route::get('user', [Controllers\Api\v1\AuthController::class, 'user']);

        // POS API Routes
        Route::group(['prefix' => 'pos'], function ()
        {
            // Expenses
            Route::get('expenses', [Controllers\Api\v1\PosController::class, 'expensesList']);
            Route::get('expenses/{id}', [Controllers\Api\v1\PosController::class, 'expenseDetails']);
            Route::post('expenses', [Controllers\Api\v1\PosController::class, 'expenseCreate']);
            Route::put('expenses/{id}', [Controllers\Api\v1\PosController::class, 'expenseEdit']);

            
            // Sales
            Route::get('sales', [Controllers\Api\v1\PosController::class, 'salesList']);
            Route::post('sales', [Controllers\Api\v1\PosController::class, 'saleCreate']);
            Route::put('sales/{id}', [Controllers\Api\v1\PosController::class, 'saleEdit']);

           
        });

        // Sync API Routes (for offline support)
        Route::group(['prefix' => 'sync'], function ()
        {
            // Download data for offline use
            Route::get('download', [Controllers\Api\v1\SyncController::class, 'download']);
            
            // Upload offline data
            Route::post('upload', [Controllers\Api\v1\SyncController::class, 'upload']);
            
            // Check sync status
            Route::get('status', [Controllers\Api\v1\SyncController::class, 'status']);
            
            // Update offline records
            Route::put('sales/{temp_id}', [Controllers\Api\v1\SyncController::class, 'updateOfflineSale']);
            Route::put('expenses/{temp_id}', [Controllers\Api\v1\SyncController::class, 'updateOfflineExpense']);
        });
    });
});





