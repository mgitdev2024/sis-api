<?php
use App\Http\Controllers\Auth\CredentialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::get('/run-migrations-and-seed', function () {
    // Artisan::call('migrate', ["--force" => true]);
    Artisan::call('migrate:fresh', ["--force" => true]);
    Artisan::call('db:seed', ["--force" => true]);
    return 'Migrations and Seed completed successfully!';
});
Route::post('login', [CredentialController::class, 'onLogin']);



#region Item Classifications
Route::post('item-classification/create', [App\Http\Controllers\Items\ItemClassificationController::class, 'onCreate']);
Route::post('item-classification/update/{id}', [App\Http\Controllers\Items\ItemClassificationController::class, 'onUpdateById']);
Route::post('item-classification/get', [App\Http\Controllers\Items\ItemClassificationController::class, 'onGetPaginatedList']);
Route::get('item-classification/get/{id}', [App\Http\Controllers\Items\ItemClassificationController::class, 'onGetById']);
Route::get('item-classification/status/{id}', [App\Http\Controllers\Items\ItemClassificationController::class, 'onChangeStatus']);
Route::delete('item-classification/delete/{id}', [App\Http\Controllers\Items\ItemClassificationController::class, 'onDeleteById']);
#endregion

#region Item Masterdata
Route::post('item-masterdata/create', [App\Http\Controllers\Items\ItemMasterdataController::class, 'onCreate']);
Route::post('item-masterdata/update/{id}', [App\Http\Controllers\Items\ItemMasterdataController::class, 'onUpdateById']);
Route::post('item-masterdata/get', [App\Http\Controllers\Items\ItemMasterdataController::class, 'onGetPaginatedList']);
Route::get('item-masterdata/get/{id}', [App\Http\Controllers\Items\ItemMasterdataController::class, 'onGetById']);
Route::get('item-masterdata/status/{id}', [App\Http\Controllers\Items\ItemMasterdataController::class, 'onChangeStatus']);
Route::delete('item-masterdata/delete/{id}', [App\Http\Controllers\Items\ItemMasterdataController::class, 'onDeleteById']);
#endregion

#region Delivery Types
Route::post('delivery/type/create', [App\Http\Controllers\Delivery\DeliveryTypeController::class, 'onCreate']);
Route::post('delivery/type/update/{id}', [App\Http\Controllers\Delivery\DeliveryTypeController::class, 'onUpdateById']);
Route::post('delivery/type/get', [App\Http\Controllers\Delivery\DeliveryTypeController::class, 'onGetPaginatedList']);
Route::get('delivery/type/get/{id}', [App\Http\Controllers\Delivery\DeliveryTypeController::class, 'onGetById']);
Route::get('delivery/type/status/{id}', [App\Http\Controllers\Delivery\DeliveryTypeController::class, 'onChangeStatus']);
Route::delete('delivery/type/delete/{id}', [App\Http\Controllers\Delivery\DeliveryTypeController::class, 'onDeleteById']);
#endregion

#region Production Orders
Route::post('production/order/create', [App\Http\Controllers\Productions\ProductionOrderController::class, 'onCreate']);
Route::post('production/order/update/{id}', [App\Http\Controllers\Productions\ProductionOrderController::class, 'onUpdateById']);
Route::post('production/order/get', [App\Http\Controllers\Productions\ProductionOrderController::class, 'onGetPaginatedList']);
Route::get('production/order/get/{id}', [App\Http\Controllers\Productions\ProductionOrderController::class, 'onGetById']);
Route::get('production/order/status/{id}', [App\Http\Controllers\Productions\ProductionOrderController::class, 'onChangeStatus']);
Route::post('production/order/bulk', [App\Http\Controllers\Productions\ProductionOrderController::class, 'onBulkUploadProductionOrder']);
#endregion

Route::group(['middleware' => ['auth:sanctum']], function () {


    Route::get('logout', [CredentialController::class, 'onLogout']); // Logout
});
