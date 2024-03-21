<?php
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
Route::get('v1/run-migrations-and-seed', function () {
    // Artisan::call('migrate', ["--force" => true]);
    Artisan::call('migrate:fresh', ["--force" => true]);
    Artisan::call('db:seed', ["--force" => true]);
    return 'Migrations and Seed completed successfully!';
});

Route::post('v1/login', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogin']);
#region Item Classifications
Route::post('v1/item/classification/create', [App\Http\Controllers\v1\Items\ItemClassificationController::class, 'onCreate']);
Route::post('v1/item/classification/update/{id}', [App\Http\Controllers\v1\Items\ItemClassificationController::class, 'onUpdateById']);
Route::post('v1/item/classification/get', [App\Http\Controllers\v1\Items\ItemClassificationController::class, 'onGetPaginatedList']);
Route::get('v1/item/classification/get/{id}', [App\Http\Controllers\v1\Items\ItemClassificationController::class, 'onGetById']);
Route::get('v1/item/classification/status/{id}', [App\Http\Controllers\v1\Items\ItemClassificationController::class, 'onChangeStatus']);
Route::delete('v1/item/classification/delete/{id}', [App\Http\Controllers\v1\Items\ItemClassificationController::class, 'onDeleteById']);
#endregion

#region Item Variant Type
Route::post('v1/item/variant/type/create', [App\Http\Controllers\v1\Items\ItemVariantTypeController::class, 'onCreate']);
Route::post('v1/item/variant/type/update/{id}', [App\Http\Controllers\v1\Items\ItemVariantTypeController::class, 'onUpdateById']);
Route::post('v1/item/variant/type/get', [App\Http\Controllers\v1\Items\ItemVariantTypeController::class, 'onGetPaginatedList']);
Route::get('v1/item/variant/type/get/{id}', [App\Http\Controllers\v1\Items\ItemVariantTypeController::class, 'onGetById']);
Route::get('v1/item/variant/type/status/{id}', [App\Http\Controllers\v1\Items\ItemVariantTypeController::class, 'onChangeStatus']);
Route::delete('v1/item/variant/type/delete/{id}', [App\Http\Controllers\v1\Items\ItemVariantTypeController::class, 'onDeleteById']);
#endregion

#region Item Masterdata
Route::post('v1/item/masterdata/create', [App\Http\Controllers\v1\Items\ItemMasterdataController::class, 'onCreate']);
Route::post('v1/item/masterdata/update/{id}', [App\Http\Controllers\v1\Items\ItemMasterdataController::class, 'onUpdateById']);
Route::post('v1/item/masterdata/get', [App\Http\Controllers\v1\Items\ItemMasterdataController::class, 'onGetPaginatedList']);
Route::get('v1/item/masterdata/get/{id}', [App\Http\Controllers\v1\Items\ItemMasterdataController::class, 'onGetById']);
Route::get('v1/item/masterdata/status/{id}', [App\Http\Controllers\v1\Items\ItemMasterdataController::class, 'onChangeStatus']);
Route::delete('v1/item/masterdata/delete/{id}', [App\Http\Controllers\v1\Items\ItemMasterdataController::class, 'onDeleteById']);
#endregion

#region Delivery Types
Route::post('v1/delivery/type/create', [App\Http\Controllers\v1\Delivery\DeliveryTypeController::class, 'onCreate']);
Route::post('v1/delivery/type/update/{id}', [App\Http\Controllers\v1\Delivery\DeliveryTypeController::class, 'onUpdateById']);
Route::post('v1/delivery/type/get', [App\Http\Controllers\v1\Delivery\DeliveryTypeController::class, 'onGetPaginatedList']);
Route::get('v1/delivery/type/get/{id}', [App\Http\Controllers\v1\Delivery\DeliveryTypeController::class, 'onGetById']);
Route::get('v1/delivery/type/status/{id}', [App\Http\Controllers\v1\Delivery\DeliveryTypeController::class, 'onChangeStatus']);
Route::delete('v1/delivery/type/delete/{id}', [App\Http\Controllers\v1\Delivery\DeliveryTypeController::class, 'onDeleteById']);
#endregion

#region Production Orders
Route::post('v1/production/order/create', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onCreate']);
Route::post('v1/production/order/update/{id}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onUpdateById']);
Route::post('v1/production/order/get', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetPaginatedList']);
Route::get('v1/production/order/get/{id}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetById']);
Route::get('v1/production/order/status/{id}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onChangeStatus']);
Route::post('v1/production/order/bulk', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onBulkUploadProductionOrder']);
Route::get('v1/production/order/current/{id?}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetCurrent']);
#endregion

#region Production OTA
Route::post('v1/production/ota/create', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onCreate']);
Route::post('v1/production/ota/update/{id}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onUpdateById']);
Route::post('v1/production/ota/get', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onGetPaginatedList']);
Route::get('v1/production/ota/get/{id}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onGetById']);
Route::get('v1/production/ota/status/{id}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onChangeStatus']);
Route::get('v1/production/ota/current/{id?}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onGetCurrent']);
#endregion

#region Production OTB
Route::post('v1/production/otb/create', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onCreate']);
Route::post('v1/production/otb/update/{id}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onUpdateById']);
Route::post('v1/production/otb/get', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onGetPaginatedList']);
Route::get('v1/production/otb/get/{id}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onGetById']);
Route::get('v1/production/otb/status/{id}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onChangeStatus']);
Route::get('v1/production/otb/current/{id?}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onGetCurrent']);
#endregion

Route::group(['middleware' => ['auth:sanctum']], function () {


    Route::get('v1/logout', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogout']); // Logout
});
