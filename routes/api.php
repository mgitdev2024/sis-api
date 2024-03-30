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
Route::post('v1/item/classification/create', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onCreate']);
Route::post('v1/item/classification/update/{id}', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onUpdateById']);
Route::post('v1/item/classification/get', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onGetPaginatedList']);
Route::get('v1/item/classification/get/{id}', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onGetById']);
Route::get('v1/item/classification/status/{id}', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onChangeStatus']);
Route::delete('v1/item/classification/delete/{id}', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onDeleteById']);
#endregion

#region Item Variant Type
Route::post('v1/item/variant/type/create', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onCreate']);
Route::post('v1/item/variant/type/update/{id}', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onUpdateById']);
Route::post('v1/item/variant/type/get', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onGetPaginatedList']);
Route::get('v1/item/variant/type/get/{id}', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onGetById']);
Route::get('v1/item/variant/type/status/{id}', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onChangeStatus']);
Route::delete('v1/item/variant/type/delete/{id}', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onDeleteById']);
#endregion

#region Item Masterdata
Route::post('v1/item/masterdata/create', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onCreate']);
Route::post('v1/item/masterdata/update/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onUpdateById']);
Route::post('v1/item/masterdata/get', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onGetPaginatedList']);
Route::get('v1/item/masterdata/get/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onGetById']);
Route::get('v1/item/masterdata/status/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onChangeStatus']);
Route::delete('v1/item/masterdata/delete/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onDeleteById']);
Route::get('v1/item/masterdata/current/{id?}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onGetCurrent']);
#endregion

#region Measurement Conversion
Route::post('v1/measurement/conversion/create', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onCreate']);
Route::post('v1/measurement/conversion/update/{id}', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onUpdateById']);
Route::post('v1/measurement/conversion/get', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onGetPaginatedList']);
Route::get('v1/measurement/conversion/get/{id}', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onGetById']);
Route::get('v1/measurement/conversion/status/{id}', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onChangeStatus']);
Route::delete('v1/measurement/conversion/delete/{id}', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onDeleteById']);
#endregion

#region Measurement UOM
Route::post('v1/measurement/uom/create', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onCreate']);
Route::post('v1/measurement/uom/update/{id}', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onUpdateById']);
Route::post('v1/measurement/uom/get', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onGetPaginatedList']);
Route::get('v1/measurement/uom/get/{id}', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onGetById']);
Route::get('v1/measurement/uom/status/{id}', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onChangeStatus']);
Route::delete('v1/measurement/uom/delete/{id}', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onDeleteById']);
#endregion

#region Facility Plant
Route::post('v1/plant/create', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onCreate']);
Route::post('v1/plant/update/{id}', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onUpdateById']);
Route::post('v1/plant/get', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onGetPaginatedList']);
Route::get('v1/plant/get/{id}', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onGetById']);
Route::get('v1/plant/status/{id}', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onChangeStatus']);
#endregion

#region Delivery Types
Route::post('v1/delivery/type/create', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onCreate']);
Route::post('v1/delivery/type/update/{id}', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onUpdateById']);
Route::post('v1/delivery/type/get', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onGetPaginatedList']);
Route::get('v1/delivery/type/get/{id}', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onGetById']);
Route::get('v1/delivery/type/status/{id}', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onChangeStatus']);
Route::delete('v1/delivery/type/delete/{id}', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onDeleteById']);
#endregion

#region Production Orders
Route::post('v1/production/order/create', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onCreate']);
Route::post('v1/production/order/update/{id}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onUpdateById']);
Route::post('v1/production/order/all', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetPaginatedList']);
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

#region Production Batch
Route::post('v1/production/batch/create', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onCreate']);
Route::post('v1/production/batch/update/{id}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onUpdateById']);
Route::post('v1/production/batch/get', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onGetPaginatedList']);
Route::get('v1/production/batch/get/{id}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onGetById']);
Route::get('v1/production/batch/current/{id?}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onGetCurrent']);
Route::get('v1/production/batch/status/{id}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onChangeStatus']);
#endregion

#region Production Items
Route::post('v1/produced/items/update/{id}', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onUpdateById']);
Route::post('v1/produced/items/get', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onGetPaginatedList']);
Route::get('v1/produced/items/get/{id}', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onGetById']);
Route::get('v1/produced/items/status/{id}', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onChangeStatus']);
Route::post('v1/produced/items/scan/deactivate', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onDeactivateItem']);
#endregion
Route::group(['middleware' => ['auth:sanctum']], function () {


    Route::get('v1/logout', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogout']); // Logout
});
