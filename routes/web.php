<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPHtmlParser\Dom;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



// Frontend Routes (Public)
Route::get('/', function () {
    return redirect('dashboard');
});

Route::group(['middleware' => ['install']], function () {

    try {
        date_default_timezone_set(get_option('timezone') ?? 'Asia/Dhaka');
    } catch (Exception $e) {
        //
    }

    Auth::routes(['register' => false]);
    Route::get('logout', [Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    //auth
    Route::group(['middleware' => ['auth']], function () {
        Route::get('/dashboard', [Controllers\DashboardController::class, 'index'])->name('dashboard');
        //Profile Controller
        Route::get('profile/show', [Controllers\ProfileController::class, 'show'])->name('profile.show');
        Route::get('profile/edit', [Controllers\ProfileController::class,'edit'])->name('profile.edit');
        Route::post('profile/update', [Controllers\ProfileController::class,'update'])->name('profile.update');
        Route::get('password/change', [Controllers\ProfileController::class,'password_change'])->name('password.change');
        Route::post('password/update', [Controllers\ProfileController::class,'update_password'])->name('password.update');

        //Settings Controller
        Route::any('general_settings', [Controllers\SettingController::class, 'general'])->name('general_settings');
        Route::any('app_settings', [Controllers\SettingController::class, 'app'])->name('app_settings');
        Route::post('store_settings', [Controllers\SettingController::class, 'store_settings'])->name('store_settings');

        //Backup Controller
        Route::any('database_backup', [Controllers\BackupController::class, 'index'])->name('database_backup');


        Route::get('notifications/deleteall', [Controllers\NotificationController::class, 'deleteall']);
        Route::resource('notifications', Controllers\NotificationController::class);



        //SystemUserController
        Route::resource('system_users', Controllers\SystemUserController::class);
        //UserController
        Route::resource('users', Controllers\UserController::class);
        //CategoryController
        Route::resource('categories', Controllers\CategoryController::class);

        // POS System Routes
        //IngredientController
        Route::resource('ingredients', Controllers\IngredientController::class);
        //ItemController
        Route::resource('items', Controllers\ItemController::class);
        //StockController
        Route::resource('stocks', Controllers\StockController::class)->only(['index', 'show']);
        Route::get('stocks/low-stock', [Controllers\StockController::class, 'lowStock'])->name('stocks.low-stock');
        Route::post('stocks/{id}/adjust', [Controllers\StockController::class, 'adjust'])->name('stocks.adjust');
        //ExpenseController
        Route::resource('expenses', Controllers\ExpenseController::class);
        //SaleController
        Route::resource('sales', Controllers\SaleController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::get('sales/items/{item_id}/variants', [Controllers\SaleController::class, 'getItemVariants'])->name('sales.item-variants');
        Route::get('sales/get-item-variants/{item_id}', [Controllers\SaleController::class, 'getItemVariants']);
        //PaymentController
        Route::resource('payments', Controllers\PaymentController::class)->only(['index', 'create', 'store', 'show']);
        //ReportController
        Route::get('reports', [Controllers\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/ingredients-consumed', [Controllers\ReportController::class, 'ingredientsConsumed'])->name('reports.ingredients-consumed');
        Route::get('reports/sales-summary', [Controllers\ReportController::class, 'salesSummary'])->name('reports.sales-summary');
        Route::get('reports/stock-status', [Controllers\ReportController::class, 'stockStatus'])->name('reports.stock-status');
        Route::get('reports/expenses', [Controllers\ReportController::class, 'expenses'])->name('reports.expenses');
        
    });
    
    Route::get('/privacy_policy', [Controllers\HomeController::class, 'privacy_policy'])->name('privacy_policy');
    Route::get('/terms_conditions', [Controllers\HomeController::class, 'terms_conditions'])->name('terms_conditions');
    
});

// Route::post('upload', [Controllers\HighlightController::class, 'upload']);

//Install Controller
Route::get('installation', [Controllers\InstallController::class, 'index']);
Route::any('installation/step/one', [Controllers\InstallController::class, 'database']);
Route::any('installation/step/two', [Controllers\InstallController::class, 'user']);
Route::any('installation/step/three', [Controllers\InstallController::class, 'settings']);

Route::any('cronjob/prediction_notification', [Controllers\CronJobController::class, 'prediction_notification']);

Route::get('/cache', function(){

    cache()->flush();

    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    return redirect('dashboard')->with('success', _lang('Cache successfully clear.'));
});

