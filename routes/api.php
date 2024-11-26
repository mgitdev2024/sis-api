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

Route::post('v1/login', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogin']);

Route::get('v1/user/access/get/{id}', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onGetAccessList']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('v1/logout', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogout']); // Logout
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('v1/run-migrations', function () {
        // Artisan::call('migrate', ["--force" => true]);
        Artisan::call('migrate', ["--force" => true]);
        return 'Migrations completed successfully!';
    });
    Route::get('v1/run-migrations-and-seed', function () {
        // Artisan::call('migrate', ["--force" => true]);
        Artisan::call('migrate:fresh', ["--force" => true]);
        Artisan::call('db:seed', ["--force" => true]);
        return 'Migrations and Seed completed successfully!';
    });

    #region Admin Access
    Route::post('v1/asset/list/upload', [App\Http\Controllers\v1\Admin\Asset\AssetListController::class, 'onCreate']);
    Route::post('v1/asset/list/keyword/get', [App\Http\Controllers\v1\Admin\Asset\AssetListController::class, 'onGetAssetListByKeyword']);
    Route::get('v1/asset/list/keyword/get/{keyword}', [App\Http\Controllers\v1\Admin\Asset\AssetListController::class, 'onGetAssetBykeyword']);
    #endregion

    #region User Access
    Route::post('v1/user/access', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onGetAccess']);
    Route::post('v1/user/access/update', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onUpdateAccess']);
    Route::post('v1/user/access/remove', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onRemoveAccess']);
    Route::post('v1/user/access/bulk', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onBulkUpload']);
    #endregion

    #region System Status
    Route::post('v1/system/admin/status/change/{system_id}', [App\Http\Controllers\v1\Admin\System\SCMSystemController::class, 'onChangeStatus']);
    Route::get('v1/system/admin/get/{system_id?}', [App\Http\Controllers\v1\Admin\System\SCMSystemController::class, 'onGet']);

    #endregion
});

Route::group(['middleware' => ['auth:sanctum', 'check.system.status:SCM-MOS']], function () {
    #region Production Orders
    Route::post('v1/production/order/create', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onCreate']);
    Route::post('v1/production/order/update/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onUpdateById']);
    Route::post('v1/production/order/paginated', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetPaginatedList']);
    Route::get('v1/production/order/all', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetAll']);
    Route::get('v1/production/order/get/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetById']);
    // Route::get('v1/production/order/selected-items/get/{production_order_id}/{delivery_type?}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetUnselectedItemCodes']);
    Route::post('v1/production/order/status/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onChangeStatus']);
    Route::post('v1/production/order/bulk', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onBulk']);
    Route::get('v1/production/order/current/{id?}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetCurrent']);
    Route::get('v1/production/order/get/batches/{production_order_id?}/{order_type?}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetBatches']);
    Route::post('v1/production/order/align/{production_order_id?}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onAlignProductionCount']);
    Route::post('v1/production/order/add/{production_order_id}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onAdditionalOtaOtb']);
    #endregion

    #region Production OTA
    Route::post('v1/production/ota/create', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onCreate']);
    Route::post('v1/production/ota/update/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onUpdateById']);
    Route::post('v1/production/ota/paginated', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetPaginatedList']);
    Route::get('v1/production/ota/all', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetAll']);
    Route::get('v1/production/ota/get/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetById']);
    Route::post('v1/production/ota/status/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onChangeStatus']);
    Route::get('v1/production/ota/for/otb/{id?}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetCurrentForOtb']);
    Route::get('v1/production/ota/current/{id?}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetCurrent']);
    Route::get('v1/production/ota/endorsement/{id?}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetEndorsedByQa']);
    Route::post('v1/production/ota/fulfill/endorsement/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onFulfillEndorsement']);
    #endregion

    #region Production OTB
    Route::post('v1/production/otb/create', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onCreate']);
    Route::post('v1/production/otb/update/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onUpdateById']);
    Route::post('v1/production/otb/paginated', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onGetPaginatedList']);
    Route::get('v1/production/otb/all', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onGetAll']);
    Route::get('v1/production/otb/get/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onGetById']);
    Route::post('v1/production/otb/status/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onChangeStatus']);
    Route::get('v1/production/otb/current/{id?}', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onGetCurrent']);
    Route::get('v1/production/otb/endorsement/{id?}', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onGetEndorsedByQa']);
    Route::post('v1/production/otb/fulfill/endorsement/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTBController::class, 'onFulfillEndorsement']);
    #endregion
    #region Production Batch
    Route::post('v1/production/batch/create', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onCreate']);
    Route::post('v1/production/batch/update/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onUpdateById']);
    Route::post('v1/production/batch/get', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onGetPaginatedList']);
    Route::get('v1/production/batch/get/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onGetById']);
    Route::get('v1/production/batch/current/{id?}/{order_type?}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onGetCurrent']);
    Route::get('v1/production/batch/metal/{order_type?}/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onGetProductionBatchMetalLine']);
    Route::post('v1/production/batch/print/initial/{id}/{item_disposition_id?}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onSetInitialPrint']);
    Route::post('v1/production/batch/align', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onAlignItemCode']);
    // Route::post('v1/production/batch/status/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onChangeStatus']);
    #endregion

    #region Production Items
    Route::post('v1/produced/items/update/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionItemController::class, 'onUpdateById']);
    Route::post('v1/produced/items/get', [App\Http\Controllers\v1\MOS\Production\ProductionItemController::class, 'onGetPaginatedList']);
    Route::get('v1/produced/items/get/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionItemController::class, 'onGetById']);
    Route::post('v1/produced/items/scan/status', [App\Http\Controllers\v1\MOS\Production\ProductionItemController::class, 'onChangeStatus']);
    Route::get('v1/produced/items/scan/details/check/{batch_id}/{item_key}/{item_quantity}/{add_info?}/{is_specify?}', [App\Http\Controllers\v1\MOS\Production\ProductionItemController::class, 'onCheckItemStatus']);
    // Route::post('v1/produced/items/scan/status/{status_id}/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionItemController::class, 'onChangeStatus']);
    // Route::post('v1/produced/items/scan/deactivate/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionItemController::class, 'onDeactivateItem']);
    #endregion

    #region Cache For Receive Items
    Route::post('v1/produced/items/cache/for-receive/create', [App\Http\Controllers\v1\MOS\Cache\ProductionForReceiveController::class, 'onCacheForReceive']);
    Route::get('v1/produced/items/cache/for-receive/current/get/{production_type}/{created_by_id}', [App\Http\Controllers\v1\MOS\Cache\ProductionForReceiveController::class, 'onGetCurrent']);
    Route::delete('v1/produced/items/cache/for-receive/delete/{production_type}/{created_by_id}', [App\Http\Controllers\v1\MOS\Cache\ProductionForReceiveController::class, 'onDelete']);
    #endregion

    #region Production Archived Batches
    Route::post('v1/production/batch/archives/data/{id}', [App\Http\Controllers\v1\MOS\Production\ArchivedBatchesController::class, 'onArchiveBatch']);
    Route::get('v1/production/batch/archives/current', [App\Http\Controllers\v1\MOS\Production\ArchivedBatchesController::class, 'onGetCurrent']);
    Route::get('v1/production/batch/archives/get/{id?}', [App\Http\Controllers\v1\MOS\Production\ArchivedBatchesController::class, 'onGetById']);
    #endregion

});

Route::group(['middleware' => ['auth:sanctum', 'check.system.status:SCM-WMS']], function () {
    #region Item Category
    Route::post('v1/item/category/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onCreate']);
    Route::post('v1/item/category/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onUpdateById']);
    Route::post('v1/item/category/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onGetPaginatedList']);
    Route::get('v1/item/category/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onGetAll']);
    Route::get('v1/item/category/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onGetById']);
    Route::post('v1/item/category/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onChangeStatus']);
    Route::delete('v1/item/category/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onDeleteById']);
    Route::post('v1/item/category/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onBulk']);
    #endregion

    #region Item Classification
    Route::post('v1/item/classification/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onCreate']);
    Route::post('v1/item/classification/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onUpdateById']);
    Route::post('v1/item/classification/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onGetPaginatedList']);
    Route::get('v1/item/classification/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onGetAll']);
    Route::get('v1/item/classification/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onGetById']);
    Route::post('v1/item/classification/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onChangeStatus']);
    Route::delete('v1/item/classification/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onDeleteById']);
    Route::post('v1/item/classification/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onBulk']);
    #endregion

    #region Measurement Conversion
    Route::post('v1/item/conversion/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onCreate']);
    Route::post('v1/item/conversion/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onUpdateById']);
    Route::post('v1/item/conversion/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onGetPaginatedList']);
    Route::get('v1/item/conversion/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onGetAll']);
    Route::get('v1/item/conversion/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onGetById']);
    Route::post('v1/item/conversion/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onChangeStatus']);
    Route::delete('v1/item/conversion/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onDeleteById']);
    Route::post('v1/item/conversion/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onBulk']);

    #endregion

    #region Item Movement
    Route::post('v1/item/movement/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onCreate']);
    Route::post('v1/item/movement/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onUpdateById']);
    Route::post('v1/item/movement/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onGetPaginatedList']);
    Route::get('v1/item/movement/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onGetAll']);
    Route::get('v1/item/movement/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onGetById']);
    Route::post('v1/item/movement/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onChangeStatus']);
    Route::delete('v1/item/movement/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onDeleteById']);
    Route::post('v1/item/movement/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onBulk']);

    #endregion

    #region Item Variant Type
    Route::post('v1/item/variant_type/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onCreate']);
    Route::post('v1/item/variant_type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onUpdateById']);
    Route::post('v1/item/variant_type/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/item/variant_type/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onGetAll']);
    Route::get('v1/item/variant_type/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onGetById']);
    Route::post('v1/item/variant_type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onChangeStatus']);
    Route::delete('v1/item/variant_type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onDeleteById']);
    Route::post('v1/item/variant_type/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onBulk']);

    #endregion

    #region Item Variant Type Multiplier
    Route::post('v1/item/variant_type_multiplier/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onCreate']);
    Route::post('v1/item/variant_type_multiplier/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onUpdateById']);
    Route::post('v1/item/variant_type_multiplier/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onGetPaginatedList']);
    Route::get('v1/item/variant_type_multiplier/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onGetAll']);
    Route::get('v1/item/variant_type_multiplier/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onGetById']);
    Route::post('v1/item/variant_type_multiplier/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onChangeStatus']);
    Route::delete('v1/item/variant_type_multiplier/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onDeleteById']);
    Route::post('v1/item/variant_type_multiplier/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onBulk']);

    #endregion

    #region Stock Type
    Route::post('v1/item/stock_type/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onCreate']);
    Route::post('v1/item/stock_type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onUpdateById']);
    Route::post('v1/item/stock_type/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/item/stock_type/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onGetAll']);
    Route::get('v1/item/stock_type/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onGetById']);
    Route::post('v1/item/stock_type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onChangeStatus']);
    Route::delete('v1/item/stock_type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onDeleteById']);
    Route::post('v1/item/stock_type/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onBulk']);

    #endregion

    #region Measurement UOM
    Route::post('v1/item/uom/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onCreate']);
    Route::post('v1/item/uom/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onUpdateById']);
    Route::post('v1/item/uom/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onGetPaginatedList']);
    Route::get('v1/item/uom/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onGetAll']);
    Route::get('v1/item/uom/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onGetById']);
    Route::post('v1/item/uom/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onChangeStatus']);
    Route::delete('v1/item/uom/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onDeleteById']);
    Route::post('v1/item/uom/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onBulk']);
    #endregion

    #region Delivery Types
    Route::post('v1/item/delivery_type/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onCreate']);
    Route::post('v1/item/delivery_type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onUpdateById']);
    Route::post('v1/item/delivery_type/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/item/delivery_type/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onGetAll']);
    Route::get('v1/item/delivery_type/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onGetById']);
    Route::post('v1/item/delivery_type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onChangeStatus']);
    Route::delete('v1/item/delivery_type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onDeleteById']);
    Route::post('v1/item/delivery_type/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onBulk']);

    #endregion

    #region Facility Plant
    Route::post('v1/storage/plant_facility/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onCreate']);
    Route::post('v1/storage/plant_facility/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onUpdateById']);
    Route::post('v1/storage/plant_facility/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onGetPaginatedList']);
    Route::get('v1/storage/plant_facility/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onGetAll']);
    Route::get('v1/storage/plant_facility/get/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onGetById']);
    Route::post('v1/storage/plant_facility/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onChangeStatus']);
    Route::delete('v1/storage/plant_facility/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onDeleteById']);
    Route::post('v1/storage/plant_facility/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onBulk']);
    #endregion


    #region Warehouse Location
    Route::post('v1/storage/warehouse/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onCreate']);
    Route::post('v1/storage/warehouse/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onUpdateById']);
    Route::post('v1/storage/warehouse/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onGetPaginatedList']);
    Route::get('v1/storage/warehouse/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onGetAll']);
    Route::get('v1/storage/warehouse/parent/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onGetChildByParentId']);
    Route::get('v1/storage/warehouse/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onGetById']);
    Route::post('v1/storage/warehouse/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onChangeStatus']);
    Route::delete('v1/storage/warehouse/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onDeleteById']);
    Route::post('v1/storage/warehouse/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onBulk']);
    #endregion


    #region Zone
    Route::post('v1/storage/zone/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onCreate']);
    Route::post('v1/storage/zone/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onUpdateById']);
    Route::post('v1/storage/zone/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onGetPaginatedList']);
    Route::get('v1/storage/zone/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onGetAll']);
    Route::get('v1/storage/zone/parent/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onGetChildByParentId']);
    Route::get('v1/storage/zone/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onGetById']);
    Route::post('v1/storage/zone/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onChangeStatus']);
    Route::delete('v1/storage/zone/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onDeleteById']);
    Route::post('v1/storage/zone/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onBulk']);
    Route::get('v1/storage/zone/item/get/{zone_id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onGetZoneItemList']);
    Route::get('v1/storage/zone/occupied/get', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onOccupiedZoneList']);
    #endregion


    #region Storage Type
    Route::post('v1/storage/type/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onCreate']);
    Route::post('v1/storage/type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onUpdateById']);
    Route::post('v1/storage/type/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/storage/type/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onGetAll']);
    Route::get('v1/storage/type/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onGetById']);
    Route::post('v1/storage/type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onChangeStatus']);
    Route::delete('v1/storage/type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onDeleteById']);
    Route::post('v1/storage/type/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onBulk']);
    #endregion


    #region Sub Location Type
    Route::post('v1/storage/sub_location_type/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onCreate']);
    Route::post('v1/storage/sub_location_type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onUpdateById']);
    Route::post('v1/storage/sub_location_type/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/storage/sub_location_type/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onGetAll']);
    Route::get('v1/storage/sub_location_type/parent/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onGetChildByParentId']);
    Route::get('v1/storage/sub_location_type/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onGetById']);
    Route::post('v1/storage/sub_location_type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onChangeStatus']);
    Route::delete('v1/storage/sub_location_type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onDeleteById']);
    Route::post('v1/storage/sub_location_type/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationTypeController::class, 'onBulk']);
    #endregion

    #region Sub Location
    Route::post('v1/storage/sub_location/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onCreate']);
    Route::post('v1/storage/sub_location/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onUpdateById']);
    Route::post('v1/storage/sub_location/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onGetPaginatedList']);
    Route::get('v1/storage/sub_location/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onGetAll']);
    Route::get('v1/storage/sub_location/parent/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onGetChildByParentId']);
    Route::get('v1/storage/sub_location/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onGetById']);
    Route::post('v1/storage/sub_location/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onChangeStatus']);
    Route::delete('v1/storage/sub_location/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onDeleteById']);
    Route::post('v1/storage/sub_location/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onBulk']);
    Route::get('v1/storage/sub_location/generate_code/get/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onGenerateCode']);
    Route::get('v1/storage/sub_location/generate_code/all/get', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onGenerateCodeAll']);
    Route::post('v1/storage/sub_location/generate_sub_location', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\SubLocationController::class, 'onGenerateSubLocation']);
    #endregion

    #region Item Masterdata
    Route::post('v1/item/masterdata/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onBulk']);
    Route::post('v1/item/masterdata/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onCreate']);
    Route::post('v1/item/masterdata/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onUpdateById']);
    Route::post('v1/item/masterdata/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetPaginatedList']);
    Route::get('v1/item/masterdata/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetAll']);
    Route::get('v1/item/masterdata/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetById']);
    Route::get('v1/item/masterdata/like/{item_code}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetLikeData']);
    Route::post('v1/item/masterdata/status/{status}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onChangeStatus']);
    Route::delete('v1/item/masterdata/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onDeleteById']);
    Route::get('v1/item/masterdata/current/{id?}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetCurrent']);
    #endregion


    #region Production History Log
    Route::post('v1/history/log/production/current/{id?}', [App\Http\Controllers\v1\History\ProductionLogController::class, 'onGetCurrent']);
    Route::get('v1/history/log/production/get/{id?}', [App\Http\Controllers\v1\History\ProductionLogController::class, 'onGetById']);
    #endregion

    #region Production Print History
    Route::post('v1/history/print/create', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onCreate']);
    Route::post('v1/history/print/update/{id}', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onUpdateById']);
    Route::post('v1/history/print/paginated', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onGetPaginatedList']);
    Route::get('v1/history/print/all', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onGetAll']);
    Route::get('v1/history/print/{id?}', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onGetById']);
    Route::get('v1/history/print/production/get/{filter?}', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onGetPrintedDetails']);
    #endregion

    #region Item Disposition
    Route::post('v1/item/disposition/update/{id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onUpdateById']);
    Route::get('v1/item/disposition/category/{type}/{status}/{filter?}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetAllCategory']);
    Route::get('v1/item/disposition/current/{id?}/{type?}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetCurrent']);
    Route::get('v1/item/disposition/all', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetAll']);
    Route::get('v1/item/disposition/{id?}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetById']);
    Route::post('v1/item/disposition/close/{id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onCloseDisposition']);
    Route::delete('v1/item/disposition/delete/{id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onDeleteById']);
    Route::post('v1/item/disposition/hold/{id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onHoldRelease']);
    Route::post('v1/item/disposition/statistics', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetOverallStats']);
    Route::get('v1/item/disposition/endorsed-by-qa/get/{item_disposition_id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetEndorsedByQaItems']);
    Route::post('v1/item/disposition/reopen', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onReopenDisposition']);
    #endregion

    #region Item Disposition Repository
    Route::get('v1/item/disposition/repo/current/{type}/{status}/{filter?}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionRepositoryController::class, 'onGet']);
    Route::get('v1/item/disposition/repo/dashboard/get/{status}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionRepositoryController::class, 'onGetDashboardReport']);
    #endregion

    #region Sub Standard Items
    Route::post('v1/item/sub-standard/create', [App\Http\Controllers\v1\QualityAssurance\SubStandardItemController::class, 'onCreate']);
    Route::get('v1/item/sub-standard/notify', [App\Http\Controllers\v1\QualityAssurance\SubStandardItemController::class, 'onGetNotification']);
    Route::get('v1/item/sub-standard/current/{status?}', [App\Http\Controllers\v1\QualityAssurance\SubStandardItemController::class, 'onGetCurrent']);
    #endregion

    #region Warehouse For Receive
    Route::get('v1/warehouse/for-receive/current/get/{reference_number}/{created_by_id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseForReceiveController::class, 'onGetCurrent']);
    Route::post('v1/warehouse/for-receive/create', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseForReceiveController::class, 'onCreate']);
    Route::delete('v1/warehouse/for-receive/delete/{reference_number}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseForReceiveController::class, 'onDelete']);
    #endregion

    #region Warehouse Receiving
    Route::get('v1/warehouse/receive/category/{status}/{production_order_id?}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onGetAllCategory']);
    Route::get('v1/warehouse/receive/current/{reference_number}/{status}/{received_status?}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onGetCurrent']);
    Route::get('v1/warehouse/receive/get/{id?}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onGetById']);
    Route::post('v1/warehouse/receive/update/{reference_number}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onUpdate']);
    Route::post('v1/warehouse/receive/complete-transaction/{reference_number}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onCompleteTransactionMVP']);
    Route::post('v1/warehouse/receive/sub-standard/{reference_number}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onSubStandard']);
    #endregion

    #region Warehouse Bulk Receiving
    Route::get('v1/warehouse/bulk/temporary-storage/get/{slid}/{status}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseBulkReceivingController::class, 'onGetTemporaryStorageItems']);
    Route::post('v1/warehouse/bulk/receive/create', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseBulkReceivingController::class, 'onCreate']);
    Route::get('v1/warehouse/bulk/receive/all/get/{created_by_id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseBulkReceivingController::class, 'onGetAll']);
    Route::post('v1/warehouse/bulk/receive/sub-standard', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseBulkReceivingController::class, 'onSubstandard']);
    Route::delete('v1/warehouse/bulk/receive/delete/{created_by_id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseBulkReceivingController::class, 'onDelete']);
    Route::post('v1/warehouse/bulk/receive/update', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseBulkReceivingController::class, 'onCreatePutAway']);

    #endregion

    #region Warehouse Put Away
    Route::post('v1/warehouse/put-away/create', [App\Http\Controllers\v1\WMS\Warehouse\WarehousePutAwayController::class, 'onCreate']);
    Route::post('v1/warehouse/put-away/sub-standard/{warehouse_put_away_id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehousePutAwayController::class, 'onSubStandard']);
    Route::get('v1/warehouse/put-away/current/{status}/{filter?}', [App\Http\Controllers\v1\WMS\Warehouse\WarehousePutAwayController::class, 'onGetCurrent']);
    Route::get('v1/warehouse/put-away/get/{id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehousePutAwayController::class, 'onGetById']);
    Route::post('v1/warehouse/put-away/complete-transaction/{put_away_reference_number}', [App\Http\Controllers\v1\WMS\Warehouse\WarehousePutAwayController::class, 'onCompleteTransaction']);
    #endregion

    #region Warehouse For Put Away
    Route::post('v1/warehouse/for/put-away/create', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseForPutAwayController::class, 'onCreate']);
    Route::post('v1/warehouse/for/put-away/update/{warehouse_put_away_id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseForPutAwayController::class, 'onUpdate']);
    Route::post('v1/warehouse/for/put-away/transfer/{warehouse_put_away_id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseForPutAwayController::class, 'onTransferItems']);
    Route::get('v1/warehouse/for/put-away/current/get/{warehouse_put_away_id}/{created_by_id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseForPutAwayController::class, 'onGetCurrent']);
    Route::delete('v1/warehouse/for/put-away/delete/{warehouse_put_away_id}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseForPutAwayController::class, 'onDelete']);
    #endregion

    #region Queued Temporary Storage
    Route::get('v1/queue/storage/temporary/{sub_location_id}', [App\Http\Controllers\v1\WMS\Storage\QueuedTemporaryStorageController::class, 'onGetCurrent']);
    Route::get('v1/queue/storage/temporary/items/get/{sub_location_id}/{status}', [App\Http\Controllers\v1\WMS\Storage\QueuedTemporaryStorageController::class, 'onGetItems']);
    Route::get('v1/queue/storage/temporary/status/get/{sub_location_id}', [App\Http\Controllers\v1\WMS\Storage\QueuedTemporaryStorageController::class, 'onGetStatus']);
    #endregion

    #region Queued Permanent Storage
    Route::post('v1/queue/storage/permanent/create', [App\Http\Controllers\v1\WMS\Storage\QueuedSubLocationController::class, 'onCreate']);
    Route::get('v1/queue/storage/permanent/current/get/{sub_location_id}/{item_id}', [App\Http\Controllers\v1\WMS\Storage\QueuedSubLocationController::class, 'onGetCurrent']);
    Route::get('v1/queue/storage/permanent/items/get/{sub_location_id}', [App\Http\Controllers\v1\WMS\Storage\QueuedSubLocationController::class, 'onGetItems']);
    Route::get('v1/queue/storage/permanent/status/get/{sub_location_id}', [App\Http\Controllers\v1\WMS\Storage\QueuedSubLocationController::class, 'onGetStatus']);
    #endregion

    #region Item Stocks Logs
    Route::get('v1/item/stock/logs/get/{item_id}/{date?}', [App\Http\Controllers\v1\WMS\Storage\StockLogController::class, 'onGetByItemCode']);
    #endregion

    #region Item Stocks Inventory
    Route::get('v1/item/stock/inventory/get/{item_id}', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onGetByItemId']);
    Route::post('v1/item/stock/inventory/bulk', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onBulk']);
    Route::post('v1/item/stock/inventory/update/{id}', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onUpdate']);
    Route::get('v1/item/stock/inventory/all/get', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onGetAll']);
    Route::get('v1/item/stock/inventory/in-stock/get/{item_id}', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onGetInStock']);
    Route::get('v1/item/stock/inventory/all-location/get/{item_id}', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onGetStockAllLocation']);
    Route::get('v1/item/stock/inventory/all-location/items/get/{sub_location_id}/{layer_level}/{item_id}', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onGetItemsPerSubLocation']);
    Route::get('v1/item/stock/inventory/zone/all/get', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onGetAllZoneLocation']);
    Route::get('v1/item/stock/inventory/zone/details/get/{zone_id}/{item_id?}', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onGetZoneDetails']);
    Route::get('v1/item/stock/inventory/zone/item/get/{zone_id}/{item_id?}', [App\Http\Controllers\v1\WMS\Storage\StockInventoryController::class, 'onGetZoneItemList']);
    #endregion

    #region Inventory Movement
    Route::get('v1/item/movement/stats/get/{date}', [App\Http\Controllers\v1\WMS\InventoryKeeping\InventoryMovementController::class, 'onGetInventoryMovementStats']);
    #endregion

    #region Stock Transfer
    Route::post('v1/stock/transfer/create', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferListController::class, 'onCreate']);
    Route::post('v1/stock/transfer/cancel/{id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferListController::class, 'onCancel']);
    Route::get('v1/stock/transfer/all/get/{status?}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferListController::class, 'onGetAll']);
    Route::get('v1/stock/transfer/get/{id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferListController::class, 'onGetById']);
    Route::get('v1/stock/transfer/request/all/get/{status?}/{filter?}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferListController::class, 'onGetStockRequestList']);
    Route::get('v1/stock/transfer/request/get/{id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferListController::class, 'onGetStockRequestById']);
    #endregion

    #region Stock Transfer Cache
    Route::post('v1/stock/transfer/cache/create', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferCacheController::class, 'onCreate']);
    Route::get('v1/stock/transfer/cache/get/{created_by_id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferCacheController::class, 'onGetCache']);
    #endregion

    #region Stock Transfer Items
    Route::get('v1/stock/transfer/item/get/{stock_transfer_item_id}/{is_check_location_only?}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferItemController::class, 'onGetById']);
    Route::get('v1/stock/transfer/item/selected-items/get/{stock_transfer_item_id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferItemController::class, 'onGetSelectedItems']);
    Route::post('v1/stock/transfer/item/scan-selected/{stock_transfer_item_id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferItemController::class, 'onScanSelectedItems']);
    Route::post('v1/stock/transfer/item/complete-transaction/{stock_transfer_item_id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransferItemController::class, 'onCompleteTransaction']);
    #endregion

    #region Stock Request For Transfer
    Route::get('v1/stock/request/for-transfer/current/get/{sub_location_id}/{layer_level}', [App\Http\Controllers\v1\WMS\InventoryKeeping\ForStockTransfer\StockRequestForTransferController::class, 'onGetCurrent']);
    Route::post('v1/stock/request/for-transfer/create', [App\Http\Controllers\v1\WMS\InventoryKeeping\ForStockTransfer\StockRequestForTransferController::class, 'onCreate']);
    Route::post('v1/stock/request/for-transfer/update/{stock_transfer_item_id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\ForStockTransfer\StockRequestForTransferController::class, 'onUpdate']);
    Route::delete('v1/stock/request/for-transfer/delete/{stock_transfer_item_id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\ForStockTransfer\StockRequestForTransferController::class, 'onDelete']);
    Route::post('v1/stock/request/for-transfer/transfer/{stock_transfer_item_id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\ForStockTransfer\StockRequestForTransferController::class, 'onTransferItems']);
    Route::post('v1/stock/request/for-transfer/transfer/sub-standard/{stock_transfer_item_id}', [App\Http\Controllers\v1\WMS\InventoryKeeping\ForStockTransfer\StockRequestForTransferController::class, 'onSubstandardItems']);
    #endregion
});
