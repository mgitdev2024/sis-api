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

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('v1/run-migrations-and-seed', function () {
        // Artisan::call('migrate', ["--force" => true]);
        Artisan::call('migrate:fresh', ["--force" => true]);
        Artisan::call('db:seed', ["--force" => true]);
        return 'Migrations and Seed completed successfully!';
    });

    #region User Access
    Route::post('v1/user/access', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onGetAccess']);
    Route::post('v1/user/access/update', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onUpdateAccess']);
    Route::post('v1/user/access/remove', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onRemoveAccess']);
    Route::get('v1/user/access/get/{id}', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onGetAccessList']);
    Route::post('v1/user/access/bulk', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onBulkUpload']);
    #endregion
    Route::get('v1/logout', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogout']); // Logout

    #region Item Category
    Route::post('v1/item_category/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onCreate']);
    Route::post('v1/item_category/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onUpdateById']);
    Route::post('v1/item_category/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onGetPaginatedList']);
    Route::get('v1/item_category/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onGetAll']);
    Route::get('v1/item_category/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onGetById']);
    Route::post('v1/item_category/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onChangeStatus']);
    Route::delete('v1/item_category/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onDeleteById']);
    Route::post('v1/item_category/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemCategoryController::class, 'onBulk']);
    #endregion

    #region Item Classification
    Route::post('v1/item_classification/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onCreate']);
    Route::post('v1/item_classification/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onUpdateById']);
    Route::post('v1/item_classification/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onGetPaginatedList']);
    Route::get('v1/item_classification/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onGetAll']);
    Route::get('v1/item_classification/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onGetById']);
    Route::post('v1/item_classification/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onChangeStatus']);
    Route::delete('v1/item_classification/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onDeleteById']);
    Route::post('v1/item_classification/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemClassificationController::class, 'onBulk']);
    #endregion

    #region Measurement Conversion
    Route::post('v1/item_conversion/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onCreate']);
    Route::post('v1/item_conversion/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onUpdateById']);
    Route::post('v1/item_conversion/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onGetPaginatedList']);
    Route::get('v1/item_conversion/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onGetAll']);
    Route::get('v1/item_conversion/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onGetById']);
    Route::post('v1/item_conversion/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onChangeStatus']);
    Route::delete('v1/item_conversion/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onDeleteById']);
    Route::post('v1/item_conversion/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemConversionController::class, 'onBulk']);

    #endregion

    #region Item Movement
    Route::post('v1/item_movement/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onCreate']);
    Route::post('v1/item_movement/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onUpdateById']);
    Route::post('v1/item_movement/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onGetPaginatedList']);
    Route::get('v1/item_movement/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onGetAll']);
    Route::get('v1/item_movement/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onGetById']);
    Route::post('v1/item_movement/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onChangeStatus']);
    Route::delete('v1/item_movement/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onDeleteById']);
    Route::post('v1/item_movement/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMovementController::class, 'onBulk']);

    #endregion

    #region Item Variant Type
    Route::post('v1/item_variant_type/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onCreate']);
    Route::post('v1/item_variant_type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onUpdateById']);
    Route::post('v1/item_variant_type/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/item_variant_type/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onGetAll']);
    Route::get('v1/item_variant_type/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onGetById']);
    Route::post('v1/item_variant_type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onChangeStatus']);
    Route::delete('v1/item_variant_type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onDeleteById']);
    Route::post('v1/item_variant_type/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeController::class, 'onBulk']);

    #endregion

    #region Item Variant Type Multiplier
    Route::post('v1/item_variant_type_multiplier/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onCreate']);
    Route::post('v1/item_variant_type_multiplier/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onUpdateById']);
    Route::post('v1/item_variant_type_multiplier/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onGetPaginatedList']);
    Route::get('v1/item_variant_type_multiplier/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onGetAll']);
    Route::get('v1/item_variant_type_multiplier/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onGetById']);
    Route::post('v1/item_variant_type_multiplier/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onChangeStatus']);
    Route::delete('v1/item_variant_type_multiplier/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onDeleteById']);
    Route::post('v1/item_variant_type_multiplier/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierController::class, 'onBulk']);

    #endregion

    #region Stock Type
    Route::post('v1/item_stock_type/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onCreate']);
    Route::post('v1/item_stock_type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onUpdateById']);
    Route::post('v1/item_stock_type/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/item_stock_type/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onGetAll']);
    Route::get('v1/item_stock_type/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onGetById']);
    Route::post('v1/item_stock_type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onChangeStatus']);
    Route::delete('v1/item_stock_type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onDeleteById']);
    Route::post('v1/item_stock_type/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemStockTypeController::class, 'onBulk']);

    #endregion

    #region Measurement UOM
    Route::post('v1/item_uom/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onCreate']);
    Route::post('v1/item_uom/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onUpdateById']);
    Route::post('v1/item_uom/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onGetPaginatedList']);
    Route::get('v1/item_uom/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onGetAll']);
    Route::get('v1/item_uom/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onGetById']);
    Route::post('v1/item_uom/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onChangeStatus']);
    Route::delete('v1/item_uom/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onDeleteById']);
    Route::post('v1/item_uom/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemUomController::class, 'onBulk']);
    #endregion

    #region Delivery Types
    Route::post('v1/item_delivery_type/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onCreate']);
    Route::post('v1/item_delivery_type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onUpdateById']);
    Route::post('v1/item_delivery_type/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/item_delivery_type/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onGetAll']);
    Route::get('v1/item_delivery_type/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onGetById']);
    Route::post('v1/item_delivery_type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onChangeStatus']);
    Route::delete('v1/item_delivery_type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onDeleteById']);
    Route::post('v1/item_delivery_type/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemDeliveryTypeController::class, 'onBulk']);

    #endregion

    #region Facility Plant
    Route::post('v1/plant/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onCreate']);
    Route::post('v1/plant/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onUpdateById']);
    Route::post('v1/plant/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onGetPaginatedList']);
    Route::get('v1/plant/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onGetAll']);
    Route::get('v1/plant/get/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onGetById']);
    Route::post('v1/plant/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onChangeStatus']);
    Route::delete('v1/plant/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onDeleteById']);
    Route::post('v1/plant/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\FacilityPlantController::class, 'onBulk']);
    #endregion

    #region Storage Type
    Route::post('v1/storage_type/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onCreate']);
    Route::post('v1/storage_type/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onUpdateById']);
    Route::post('v1/storage_type/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/storage_type/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onGetAll']);
    Route::get('v1/storage_type/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onGetById']);
    Route::post('v1/storage_type/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onChangeStatus']);
    Route::delete('v1/storage_type/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onDeleteById']);
    Route::post('v1/storage_type/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\StorageTypeController::class, 'onBulk']);
    #endregion

    #region Warehouse Location
    Route::post('v1/warehouse_location/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onCreate']);
    Route::post('v1/warehouse_location/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onUpdateById']);
    Route::post('v1/warehouse_location/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onGetPaginatedList']);
    Route::get('v1/warehouse_location/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onGetAll']);
    Route::get('v1/warehouse_location/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onGetById']);
    Route::post('v1/warehouse_location/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onChangeStatus']);
    Route::delete('v1/warehouse_location/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onDeleteById']);
    Route::post('v1/warehouse_location/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\WarehouseController::class, 'onBulk']);
    #endregion

    #region Zone
    Route::post('v1/zone/create', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onCreate']);
    Route::post('v1/zone/update/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onUpdateById']);
    Route::post('v1/zone/paginated', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onGetPaginatedList']);
    Route::get('v1/zone/all', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onGetAll']);
    Route::get('v1/zone/get/{id?}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onGetById']);
    Route::post('v1/zone/status/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onChangeStatus']);
    Route::delete('v1/zone/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onDeleteById']);
    Route::post('v1/zone/bulk', [App\Http\Controllers\v1\WMS\Settings\StorageMasterData\ZoneController::class, 'onBulk']);
    #endregion

    #region Item Masterdata
    Route::post('v1/item/masterdata/bulk', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onBulk']);
    Route::post('v1/item/masterdata/create', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onCreate']);
    Route::post('v1/item/masterdata/update/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onUpdateById']);
    Route::post('v1/item/masterdata/paginated', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetPaginatedList']);
    Route::get('v1/item/masterdata/all', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetAll']);
    Route::get('v1/item/masterdata/get/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetById']);
    Route::post('v1/item/masterdata/status/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onChangeStatus']);
    Route::delete('v1/item/masterdata/delete/{id}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onDeleteById']);
    Route::get('v1/item/masterdata/current/{id?}', [App\Http\Controllers\v1\WMS\Settings\ItemMasterData\ItemMasterdataController::class, 'onGetCurrent']);
    #endregion

    #region Production Orders
    Route::post('v1/production/order/create', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onCreate']);
    Route::post('v1/production/order/update/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onUpdateById']);
    Route::post('v1/production/order/paginated', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetPaginatedList']);
    Route::get('v1/production/order/all', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetAll']);
    Route::get('v1/production/order/get/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetById']);
    Route::post('v1/production/order/status/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onChangeStatus']);
    Route::post('v1/production/order/bulk', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onBulk']);
    Route::get('v1/production/order/current/{id?}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetCurrent']);
    Route::get('v1/production/order/get/batches/{id?}/{order_type?}', [App\Http\Controllers\v1\MOS\Production\ProductionOrderController::class, 'onGetBatches']);
    #endregion

    #region Production OTA
    Route::post('v1/production/ota/create', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onCreate']);
    Route::post('v1/production/ota/update/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onUpdateById']);
    Route::post('v1/production/ota/paginated', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetPaginatedList']);
    Route::get('v1/production/ota/all', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetAll']);
    Route::get('v1/production/ota/get/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onGetById']);
    Route::post('v1/production/ota/status/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionOTAController::class, 'onChangeStatus']);
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
    Route::get('v1/production/batch/metal/{order_type?}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onGetProductionBatchMetalLine']);
    Route::post('v1/production/batch/print/initial/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onSetInitialPrint']);
    // Route::post('v1/production/batch/status/{id}', [App\Http\Controllers\v1\MOS\Production\ProductionBatchController::class, 'onChangeStatus']);
    #endregion

    #region Production Items
    Route::post('v1/produced/items/update/{id}', [App\Http\Controllers\v1\MOS\Production\ProducedItemController::class, 'onUpdateById']);
    Route::post('v1/produced/items/get', [App\Http\Controllers\v1\MOS\Production\ProducedItemController::class, 'onGetPaginatedList']);
    Route::get('v1/produced/items/get/{id}', [App\Http\Controllers\v1\MOS\Production\ProducedItemController::class, 'onGetById']);
    // Route::post('v1/produced/items/scan/deactivate/{id}', [App\Http\Controllers\v1\MOS\Production\ProducedItemController::class, 'onDeactivateItem']);
    Route::post('v1/produced/items/scan/status', [App\Http\Controllers\v1\MOS\Production\ProducedItemController::class, 'onChangeStatus']);
    Route::get('v1/produced/items/scan/status/check/{id}/{item_key}', [App\Http\Controllers\v1\MOS\Production\ProducedItemController::class, 'onCheckItemStatus']);
    // Route::post('v1/produced/items/scan/status/{status_id}/{id}', [App\Http\Controllers\v1\MOS\Production\ProducedItemController::class, 'onChangeStatus']);
    #endregion

    #region Production Archived Batches
    Route::post('v1/production/batch/archives/data/{id}', [App\Http\Controllers\v1\MOS\Production\ArchivedBatchesController::class, 'onArchiveBatch']);
    Route::get('v1/production/batch/archives/current', [App\Http\Controllers\v1\MOS\Production\ArchivedBatchesController::class, 'onGetCurrent']);
    Route::get('v1/production/batch/archives/get/{id?}', [App\Http\Controllers\v1\MOS\Production\ArchivedBatchesController::class, 'onGetById']);
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
    #endregion

    #region Item Disposition
    Route::post('v1/item/disposition/update/{id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onUpdateById']);
    Route::get('v1/item/disposition/category/{type}/{status}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetAllCategory']);
    Route::get('v1/item/disposition/current/{id?}/{type?}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetCurrent']);
    Route::get('v1/item/disposition/all', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetAll']);
    Route::get('v1/item/disposition/{id?}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetById']);
    Route::post('v1/item/disposition/close/{id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onCloseDisposition']);
    Route::delete('v1/item/disposition/delete/{id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onDeleteById']);
    Route::post('v1/item/disposition/hold/{id}', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onHoldRelease']);
    Route::post('v1/item/disposition/statistics', [App\Http\Controllers\v1\QualityAssurance\ItemDispositionController::class, 'onGetOverallStats']);
    #endregion

    #region Warehouse Receiving
    Route::get('v1/warehouse/receive/category/{status}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onGetAllCategory']);
    Route::get('v1/warehouse/receive/current/{reference_number}/{status}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onGetCurrent']);
    Route::get('v1/warehouse/receive/get/{id?}', [App\Http\Controllers\v1\WMS\Warehouse\WarehouseReceivingController::class, 'onGetById']);
    #endregion

});
