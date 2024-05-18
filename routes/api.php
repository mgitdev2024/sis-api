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

    Route::get('v1/logout', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogout']); // Logout

    #region Item Category
    Route::post('v1/item/category/create', [App\Http\Controllers\v1\Settings\Items\ItemCategoryController::class, 'onCreate']);
    Route::post('v1/item/category/update/{id}', [App\Http\Controllers\v1\Settings\Items\ItemCategoryController::class, 'onUpdateById']);
    Route::post('v1/item/category/paginated', [App\Http\Controllers\v1\Settings\Items\ItemCategoryController::class, 'onGetPaginatedList']);
    Route::get('v1/item/category/all', [App\Http\Controllers\v1\Settings\Items\ItemCategoryController::class, 'onGetAll']);
    Route::get('v1/item/category/get/{id}', [App\Http\Controllers\v1\Settings\Items\ItemCategoryController::class, 'onGetById']);
    Route::post('v1/item/category/status/{id}', [App\Http\Controllers\v1\Settings\Items\ItemCategoryController::class, 'onChangeStatus']);
    Route::delete('v1/item/category/delete/{id}', [App\Http\Controllers\v1\Settings\Items\ItemCategoryController::class, 'onDeleteById']);
    Route::post('v1/item/category/bulk', [App\Http\Controllers\v1\Settings\Items\ItemCategoryController::class, 'onBulk']);
    #endregion

    #region Item Classification
    Route::post('v1/item/classification/create', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onCreate']);
    Route::post('v1/item/classification/update/{id}', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onUpdateById']);
    Route::post('v1/item/classification/paginated', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onGetPaginatedList']);
    Route::get('v1/item/classification/all', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onGetAll']);
    Route::get('v1/item/classification/get/{id}', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onGetById']);
    Route::post('v1/item/classification/status/{id}', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onChangeStatus']);
    Route::delete('v1/item/classification/delete/{id}', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onDeleteById']);
    Route::post('v1/item/classification/bulk', [App\Http\Controllers\v1\Settings\Items\ItemClassificationController::class, 'onBulk']);
    #endregion

    #region Item Variant Type
    Route::post('v1/item/variant/type/create', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onCreate']);
    Route::post('v1/item/variant/type/update/{id}', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onUpdateById']);
    Route::post('v1/item/variant/type/paginated', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/item/variant/type/all', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onGetAll']);
    Route::get('v1/item/variant/type/get/{id}', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onGetById']);
    Route::post('v1/item/variant/type/status/{id}', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onChangeStatus']);
    Route::delete('v1/item/variant/type/delete/{id}', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onDeleteById']);
    Route::post('v1/item/variant/type/bulk', [App\Http\Controllers\v1\Settings\Items\ItemVariantTypeController::class, 'onBulk']);

    #endregion

    #region Item Masterdata
    Route::post('v1/item/masterdata/bulk', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onBulk']);
    Route::post('v1/item/masterdata/create', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onCreate']);
    Route::post('v1/item/masterdata/update/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onUpdateById']);
    Route::post('v1/item/masterdata/paginated', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onGetPaginatedList']);
    Route::get('v1/item/masterdata/all', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onGetAll']);
    Route::get('v1/item/masterdata/get/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onGetById']);
    Route::post('v1/item/masterdata/status/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onChangeStatus']);
    Route::delete('v1/item/masterdata/delete/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onDeleteById']);
    Route::get('v1/item/masterdata/current/{id?}', [App\Http\Controllers\v1\Settings\Items\ItemMasterdataController::class, 'onGetCurrent']);
    #endregion

    #region Measurement Conversion
    Route::post('v1/conversion/create', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onCreate']);
    Route::post('v1/conversion/update/{id}', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onUpdateById']);
    Route::post('v1/conversion/paginated', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onGetPaginatedList']);
    Route::get('v1/conversion/all', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onGetAll']);
    Route::get('v1/conversion/get/{id}', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onGetById']);
    Route::post('v1/conversion/status/{id}', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onChangeStatus']);
    Route::delete('v1/conversion/delete/{id}', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onDeleteById']);
    Route::post('v1/conversion/bulk', [App\Http\Controllers\v1\Settings\Measurements\ConversionController::class, 'onBulk']);

    #endregion

    #region Measurement UOM
    Route::post('v1/uom/create', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onCreate']);
    Route::post('v1/uom/update/{id}', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onUpdateById']);
    Route::post('v1/uom/paginated', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onGetPaginatedList']);
    Route::get('v1/uom/all', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onGetAll']);
    Route::get('v1/uom/get/{id}', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onGetById']);
    Route::post('v1/uom/status/{id}', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onChangeStatus']);
    Route::delete('v1/uom/delete/{id}', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onDeleteById']);
    Route::post('v1/uom/bulk', [App\Http\Controllers\v1\Settings\Measurements\UomController::class, 'onBulk']);

    #endregion

    #region Facility Plant
    Route::post('v1/plant/create', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onCreate']);
    Route::post('v1/plant/update/{id}', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onUpdateById']);
    Route::post('v1/plant/paginated', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onGetPaginatedList']);
    Route::get('v1/plant/all', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onGetAll']);
    Route::get('v1/plant/get/{id}', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onGetById']);
    Route::post('v1/plant/status/{id}', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onChangeStatus']);
    Route::delete('v1/plant/delete/{id}', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onDeleteById']);
    Route::post('v1/plant/bulk', [App\Http\Controllers\v1\Settings\Facility\PlantController::class, 'onBulk']);

    #endregion

    #region Delivery Types
    Route::post('v1/delivery/type/create', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onCreate']);
    Route::post('v1/delivery/type/update/{id}', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onUpdateById']);
    Route::post('v1/delivery/type/paginated', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/delivery/type/all', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onGetAll']);
    Route::get('v1/delivery/type/get/{id}', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onGetById']);
    Route::post('v1/delivery/type/status/{id}', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onChangeStatus']);
    Route::delete('v1/delivery/type/delete/{id}', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onDeleteById']);
    Route::post('v1/delivery/type/bulk', [App\Http\Controllers\v1\Settings\Delivery\DeliveryTypeController::class, 'onBulk']);

    #endregion

    #region Production Orders
    Route::post('v1/production/order/create', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onCreate']);
    Route::post('v1/production/order/update/{id}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onUpdateById']);
    Route::post('v1/production/order/paginated', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetPaginatedList']);
    Route::get('v1/production/order/all', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetAll']);
    Route::get('v1/production/order/get/{id}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetById']);
    Route::post('v1/production/order/status/{id}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onChangeStatus']);
    Route::post('v1/production/order/bulk', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onBulk']);
    Route::get('v1/production/order/current/{id?}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetCurrent']);
    Route::get('v1/production/order/get/batches/{id?}/{order_type?}', [App\Http\Controllers\v1\Productions\ProductionOrderController::class, 'onGetBatches']);
    #endregion

    #region Production OTA
    Route::post('v1/production/ota/create', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onCreate']);
    Route::post('v1/production/ota/update/{id}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onUpdateById']);
    Route::post('v1/production/ota/paginated', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onGetPaginatedList']);
    Route::get('v1/production/ota/all', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onGetAll']);
    Route::get('v1/production/ota/get/{id}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onGetById']);
    Route::post('v1/production/ota/status/{id}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onChangeStatus']);
    Route::get('v1/production/ota/current/{id?}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onGetCurrent']);
    Route::get('v1/production/ota/endorsement/{id?}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onGetEndorsedByQa']);
    Route::post('v1/production/ota/fulfill/endorsement/{id}', [App\Http\Controllers\v1\Productions\ProductionOTAController::class, 'onFulfillEndorsement']);
    #endregion

    #region Production OTB
    Route::post('v1/production/otb/create', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onCreate']);
    Route::post('v1/production/otb/update/{id}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onUpdateById']);
    Route::post('v1/production/otb/paginated', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onGetPaginatedList']);
    Route::get('v1/production/otb/all', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onGetAll']);
    Route::get('v1/production/otb/get/{id}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onGetById']);
    Route::post('v1/production/otb/status/{id}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onChangeStatus']);
    Route::get('v1/production/otb/current/{id?}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onGetCurrent']);
    Route::get('v1/production/otb/endorsement/{id?}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onGetEndorsedByQa']);
    Route::post('v1/production/otb/fulfill/endorsement/{id}', [App\Http\Controllers\v1\Productions\ProductionOTBController::class, 'onFulfillEndorsement']);
    #endregion
    #region Production Batch
    Route::post('v1/production/batch/create', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onCreate']);
    Route::post('v1/production/batch/update/{id}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onUpdateById']);
    Route::post('v1/production/batch/get', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onGetPaginatedList']);
    Route::get('v1/production/batch/get/{id}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onGetById']);
    Route::get('v1/production/batch/current/{id?}/{order_type?}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onGetCurrent']);
    Route::get('v1/production/batch/metal/{order_type?}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onGetProductionBatchMetalLine']);
    Route::post('v1/production/batch/print/initial/{id}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onSetInitialPrint']);
    // Route::post('v1/production/batch/status/{id}', [App\Http\Controllers\v1\Productions\ProductionBatchController::class, 'onChangeStatus']);
    #endregion

    #region Production Items
    Route::post('v1/produced/items/update/{id}', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onUpdateById']);
    Route::post('v1/produced/items/get', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onGetPaginatedList']);
    Route::get('v1/produced/items/get/{id}', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onGetById']);
    // Route::post('v1/produced/items/scan/deactivate/{id}', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onDeactivateItem']);
    Route::post('v1/produced/items/scan/status', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onChangeStatus']);
    Route::get('v1/produced/items/scan/status/check/{id}/{item_key}', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onCheckItemStatus']);


    // Route::post('v1/produced/items/scan/status/{status_id}/{id}', [App\Http\Controllers\v1\Productions\ProducedItemController::class, 'onChangeStatus']);

    #region Category
    Route::post('v1/category/create', [App\Http\Controllers\v1\Settings\Category\CategoryController::class, 'onCreate']);
    Route::post('v1/category/update/{id}', [App\Http\Controllers\v1\Settings\Category\CategoryController::class, 'onUpdateById']);
    Route::post('v1/category/paginated', [App\Http\Controllers\v1\Settings\Category\CategoryController::class, 'onGetPaginatedList']);
    Route::get('v1/category/all', [App\Http\Controllers\v1\Settings\Category\CategoryController::class, 'onGetAll']);
    Route::get('v1/category/{id?}', [App\Http\Controllers\v1\Settings\Category\CategoryController::class, 'onGetById']);
    Route::post('v1/category/status/{id}', [App\Http\Controllers\v1\Settings\Category\CategoryController::class, 'onChangeStatus']);
    Route::delete('v1/category/delete/{id}', [App\Http\Controllers\v1\Settings\Category\CategoryController::class, 'onDeleteById']);
    Route::post('v1/category/bulk', [App\Http\Controllers\v1\Settings\Category\CategoryController::class, 'onBulk']);
    #endregion

    #region Sub Category
    Route::post('v1/sub_category/create', [App\Http\Controllers\v1\Settings\Category\SubCategoryController::class, 'onCreate']);
    Route::post('v1/sub_category/update/{id}', [App\Http\Controllers\v1\Settings\Category\SubCategoryController::class, 'onUpdateById']);
    Route::post('v1/sub_category/paginated', [App\Http\Controllers\v1\Settings\Category\SubCategoryController::class, 'onGetPaginatedList']);
    Route::get('v1/sub_category/all', [App\Http\Controllers\v1\Settings\Category\SubCategoryController::class, 'onGetAll']);
    Route::get('v1/sub_category/{id?}', [App\Http\Controllers\v1\Settings\Category\SubCategoryController::class, 'onGetById']);
    Route::post('v1/sub_category/status/{id}', [App\Http\Controllers\v1\Settings\Category\SubCategoryController::class, 'onChangeStatus']);
    Route::delete('v1/sub_category/delete/{id}', [App\Http\Controllers\v1\Settings\Category\SubCategoryController::class, 'onDeleteById']);
    Route::post('v1/sub_category/bulk', [App\Http\Controllers\v1\Settings\Category\SubCategoryController::class, 'onBulk']);

    #endregion

    #region Item Movement
    Route::post('v1/item_movement/create', [App\Http\Controllers\v1\Settings\Items\ItemMovementController::class, 'onCreate']);
    Route::post('v1/item_movement/update/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMovementController::class, 'onUpdateById']);
    Route::post('v1/item_movement/paginated', [App\Http\Controllers\v1\Settings\Items\ItemMovementController::class, 'onGetPaginatedList']);
    Route::get('v1/item_movement/all', [App\Http\Controllers\v1\Settings\Items\ItemMovementController::class, 'onGetAll']);
    Route::get('v1/item_movement/get/{id?}', [App\Http\Controllers\v1\Settings\Items\ItemMovementController::class, 'onGetById']);
    Route::post('v1/item_movement/status/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMovementController::class, 'onChangeStatus']);
    Route::delete('v1/item_movement/delete/{id}', [App\Http\Controllers\v1\Settings\Items\ItemMovementController::class, 'onDeleteById']);
    Route::post('v1/item_movement/bulk', [App\Http\Controllers\v1\Settings\Items\ItemMovementController::class, 'onBulk']);

    #endregion

    #region Stock Type
    Route::post('v1/stock_type/create', [App\Http\Controllers\v1\Settings\StockType\StockTypeController::class, 'onCreate']);
    Route::post('v1/stock_type/update/{id}', [App\Http\Controllers\v1\Settings\StockType\StockTypeController::class, 'onUpdateById']);
    Route::post('v1/stock_type/paginated', [App\Http\Controllers\v1\Settings\StockType\StockTypeController::class, 'onGetPaginatedList']);
    Route::get('v1/stock_type/all', [App\Http\Controllers\v1\Settings\StockType\StockTypeController::class, 'onGetAll']);
    Route::get('v1/stock_type/get/{id?}', [App\Http\Controllers\v1\Settings\StockType\StockTypeController::class, 'onGetById']);
    Route::post('v1/stock_type/status/{id}', [App\Http\Controllers\v1\Settings\StockType\StockTypeController::class, 'onChangeStatus']);
    Route::delete('v1/stock_type/delete/{id}', [App\Http\Controllers\v1\Settings\StockType\StockTypeController::class, 'onDeleteById']);
    Route::post('v1/stock_type/bulk', [App\Http\Controllers\v1\Settings\StockType\StockTypeController::class, 'onBulk']);

    #endregion

    #region Storage Type
    Route::post('v1/storage_type/create', [App\Http\Controllers\v1\Settings\StorageType\StorageTypeContoller::class, 'onCreate']);
    Route::post('v1/storage_type/update/{id}', [App\Http\Controllers\v1\Settings\StorageType\StorageTypeContoller::class, 'onUpdateById']);
    Route::post('v1/storage_type/paginated', [App\Http\Controllers\v1\Settings\StorageType\StorageTypeContoller::class, 'onGetPaginatedList']);
    Route::get('v1/storage_type/all', [App\Http\Controllers\v1\Settings\StorageType\StorageTypeContoller::class, 'onGetAll']);
    Route::get('v1/storage_type/get/{id?}', [App\Http\Controllers\v1\Settings\StorageType\StorageTypeContoller::class, 'onGetById']);
    Route::post('v1/storage_type/status/{id}', [App\Http\Controllers\v1\Settings\StorageType\StorageTypeContoller::class, 'onChangeStatus']);
    Route::delete('v1/storage_type/delete/{id}', [App\Http\Controllers\v1\Settings\StorageType\StorageTypeContoller::class, 'onDeleteById']);
    Route::post('v1/storage_type/bulk', [App\Http\Controllers\v1\Settings\StorageType\StorageTypeContoller::class, 'onBulk']);
    #endregion

    #region Warehouse Location
    Route::post('v1/warehouse_location/create', [App\Http\Controllers\v1\Settings\Warehouse\WarehouseController::class, 'onCreate']);
    Route::post('v1/warehouse_location/update/{id}', [App\Http\Controllers\v1\Settings\Warehouse\WarehouseController::class, 'onUpdateById']);
    Route::post('v1/warehouse_location/paginated', [App\Http\Controllers\v1\Settings\Warehouse\WarehouseController::class, 'onGetPaginatedList']);
    Route::get('v1/warehouse_location/all', [App\Http\Controllers\v1\Settings\Warehouse\WarehouseController::class, 'onGetAll']);
    Route::get('v1/warehouse_location/get/{id?}', [App\Http\Controllers\v1\Settings\Warehouse\WarehouseController::class, 'onGetById']);
    Route::post('v1/warehouse_location/status/{id}', [App\Http\Controllers\v1\Settings\Warehouse\WarehouseController::class, 'onChangeStatus']);
    Route::delete('v1/warehouse_location/delete/{id}', [App\Http\Controllers\v1\Settings\Warehouse\WarehouseController::class, 'onDeleteById']);
    Route::post('v1/warehouse_location/bulk', [App\Http\Controllers\v1\Settings\Warehouse\WarehouseController::class, 'onBulk']);
    #endregion

    #region Zone
    Route::post('v1/zone/create', [App\Http\Controllers\v1\Settings\Zone\ZoneController::class, 'onCreate']);
    Route::post('v1/zone/update/{id}', [App\Http\Controllers\v1\Settings\Zone\ZoneController::class, 'onUpdateById']);
    Route::post('v1/zone/paginated', [App\Http\Controllers\v1\Settings\Zone\ZoneController::class, 'onGetPaginatedList']);
    Route::get('v1/zone/all', [App\Http\Controllers\v1\Settings\Zone\ZoneController::class, 'onGetAll']);
    Route::get('v1/zone/get/{id?}', [App\Http\Controllers\v1\Settings\Zone\ZoneController::class, 'onGetById']);
    Route::post('v1/zone/status/{id}', [App\Http\Controllers\v1\Settings\Zone\ZoneController::class, 'onChangeStatus']);
    Route::delete('v1/zone/delete/{id}', [App\Http\Controllers\v1\Settings\Zone\ZoneController::class, 'onDeleteById']);
    Route::post('v1/zone/bulk', [App\Http\Controllers\v1\Settings\Zone\ZoneController::class, 'onBulk']);
    #endregion


    #region Print History
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

    #region Print History
    Route::post('v1/history/print/create', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onCreate']);
    Route::get('v1/history/print/all', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onGetAll']);
    Route::get('v1/history/print/{id?}', [App\Http\Controllers\v1\History\PrintHistoryController::class, 'onGetById']);
    #endregion

    #region Archived Batches
    Route::post('v1/production/batch/archives/data/{id}', [App\Http\Controllers\v1\Productions\ArchivedBatchesController::class, 'onArchiveBatch']);
    Route::get('v1/production/batch/archives/current', [App\Http\Controllers\v1\Productions\ArchivedBatchesController::class, 'onGetCurrent']);
    Route::get('v1/production/batch/archives/get/{id?}', [App\Http\Controllers\v1\Productions\ArchivedBatchesController::class, 'onGetById']);
    #endregion

    #region Production History Log
    Route::post('v1/history/log/production/current/{id?}', [App\Http\Controllers\v1\History\ProductionHistoricalLogController::class, 'onGetCurrent']);
    Route::get('v1/history/log/production/get/{id?}', [App\Http\Controllers\v1\History\ProductionHistoricalLogController::class, 'onGetById']);
    #endregion

    #region Warehouse Receiving
    Route::get('v1/warehouse/receive/category/{status}', [App\Http\Controllers\v1\Warehouse\WarehouseReceivingController::class, 'onGetAllCategory']);
    Route::get('v1/warehouse/receive/current/{status}', [App\Http\Controllers\v1\Warehouse\WarehouseReceivingController::class, 'onGetCurrent']);
    Route::get('v1/warehouse/receive/get/{id?}', [App\Http\Controllers\v1\Warehouse\WarehouseReceivingController::class, 'onGetById']);
    #endregion

});
