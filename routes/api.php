<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

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
Route::post('v1/login', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogin']);
Route::post('v1/store/receive-inventory/{is_internal?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryController::class, 'onCreate']);
Route::post('v1/stock/transfer/update/{id}', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onUpdate']);

Route::prefix('v1/public')->middleware('check.api.key')->group(function () {
    #region Reports
    Route::post('/reports/store/receive-inventory/delivery-receiving', [App\Http\Controllers\v1\Report\StoreReceivingReportController::class, 'onGenerateDeliveryReceivingReport']);
    Route::post('/reports/stock/inventory/daily-movement', [App\Http\Controllers\v1\Report\StockInventoryReportController::class, 'onGenerateDailyMovementReport']);
    Route::post('/reports/stock/conversion/daily', [App\Http\Controllers\v1\Report\StockConversionReportController::class, 'onGenerateDailyReport']);
    Route::post('/reports/stock/out/daily', [App\Http\Controllers\v1\Report\StockOutReportController::class, 'onGenerateDailyReport']);
    Route::post('/reports/stock/transfer/daily', [App\Http\Controllers\v1\Report\StockTransferReportController::class, 'onGenerateDailyReport']);
    Route::post('/reports/stock/pullout/daily', [App\Http\Controllers\v1\Report\StockPulloutReportController::class, 'onGenerateDailyReport']);
    Route::post('/reports/stock/count/daily', [App\Http\Controllers\v1\Report\StockCountReportController::class, 'onGenerateDailyReport']);
    #endregion

    #region Store Consolidation Cache
    Route::post('/store-consolidation-cache/create', [App\Http\Controllers\v1\Store\StoreConsolidationCacheController::class, 'onCreate']);
    #endregion

    #region Generated Report Data
    Route::get('/generated-report/get/{model_name}', [App\Http\Controllers\v1\Report\GeneratedReportDataController::class, 'onGet']);
    Route::get('/generated-report/id/get/{id}', [App\Http\Controllers\v1\Report\GeneratedReportDataController::class, 'onGetById']);
    Route::post('/generated-report/filter/get', [App\Http\Controllers\v1\Report\GeneratedReportDataController::class, 'onGetByFilter']);
    Route::post('/generated-report/id/delete/{id}', [App\Http\Controllers\v1\Report\GeneratedReportDataController::class, 'onDeleteById']);
    #endregion
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('v1/check/token', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onCheckToken']); // Logout
    Route::post('v1/logout', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogout']); // Logout

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
    Route::get('v1/user/all-access-info', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onGetAccessInfo']);
    Route::post('v1/user/access', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onGetAccess']);
    Route::post('v1/user/access/update', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onUpdateAccess']);
    Route::post('v1/user/access/remove', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onRemoveAccess']);
    Route::post('v1/user/access/bulk', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onBulkUpload']);
    Route::get('v1/user/access/get/{id}', [App\Http\Controllers\v1\Access\AccessManagementController::class, 'onGetAccessList']);

    // User Access Management

    Route::get('v1/user/store-info/{employee_id}', [App\Http\Controllers\UserStoreController::class, 'getStoreInfo']);
    Route::post('v1/user/store-info', [App\Http\Controllers\UserStoreController::class, 'updateStoreInfo']);
    Route::post('v1/user/store-info/remove', [App\Http\Controllers\UserStoreController::class, 'removeStoreInfo']);

    #endregion

    #region System Status
    Route::post('v1/system/admin/status/change/{system_id}', [App\Http\Controllers\v1\Admin\System\SCMSystemController::class, 'onChangeStatus']);
    Route::get('v1/system/admin/get/{system_id?}', [App\Http\Controllers\v1\Admin\System\SCMSystemController::class, 'onGet']);
    #endregion

    // Cache Store
    // Route::post('v1/store/cache', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onStoreCache']);
});

Route::group(['middleware' => ['auth:sanctum', 'check.system.status:SIS']], function () {
    #region Stock Inventory Item Count
    Route::get('v1/stock/inventory-item-count/current/get/{store_inventory_count_id?}', [App\Http\Controllers\v1\Stock\StockInventoryItemCountController::class, 'onGetById']);
    Route::post('v1/stock/inventory-item-count/update/{store_inventory_count_id}', [App\Http\Controllers\v1\Stock\StockInventoryItemCountController::class, 'onUpdate']);
    Route::post('v1/stock/inventory-item-count/post/{store_inventory_count_id}', [App\Http\Controllers\v1\Stock\StockInventoryItemCountController::class, 'onPost']);
    #endregion

    #region Stock Inventory Count
    Route::post('v1/stock/inventory-count/create', [App\Http\Controllers\v1\Stock\StockInventoryCountController::class, 'onCreate']);
    Route::post('v1/stock/inventory-count/bulk', [App\Http\Controllers\v1\Stock\StockInventoryCountController::class, 'onBulk']);
    #endregion

    #region Stock Inventory
    Route::post('v1/stock/inventory/generate-initial-items', [App\Http\Controllers\v1\Stock\StockInventoryController::class, 'onGenerateInitialInventory']);
    Route::post('v1/stock/inventory-count/cancel/{id}', [App\Http\Controllers\v1\Stock\StockInventoryCountController::class, 'onCancel']);
    Route::post('v1/stock/inventory/sync', [App\Http\Controllers\v1\Stock\StockInventoryController::class, 'onSyncItemList']);
    #endregion
});

Route::group(['middleware' => ['auth:sanctum', 'check.pending.stock.count', 'check.system.status:SIS']], function () {
    #region Store Receiving Inventory
    Route::post('v1/store/receive-inventory-goods-issue', [App\Http\Controllers\v1\Store\StoreReceivingInventoryController::class, 'onCreateReceivingFromGI']);
    #endregion
    #region Store Receiving Inventory
    Route::get('v1/store/receive-inventory/current/get/{status}/{store_code?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryController::class, 'onGetCurrent']);
    Route::get('v1/store/receive-inventory/get/{store_receiving_inventory_id}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryController::class, 'onGetById']);
    #endregion

    #region Store Receiving Inventory Item
    Route::get('v1/store/receive-inventory-item/current/get/{store_code}/{order_type}/{is_received}/{status?}/{reference_number?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onGetCurrent']);
    Route::post('v1/store/receive-inventory-item/manual', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onGetCheckedManual']);

    Route::get('v1/store/receive-inventory-item/category/get/{store_code}/{status?}/{back_date?}/{sub_unit?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onGetCategory']);
    Route::post('v1/store/receive-inventory-item/scan/{store_code}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onScanItems']);
    Route::post('v1/store/receive-inventory-item/complete/{reference_number}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onComplete']);
    Route::post('v1/store/receive-inventory-item/add-remarks/{reference_number}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onAddRemarks']);

    Route::get('v1/store/receive-inventory-item/filter/order-type/get/{store_code}/{reference_number}/{sub_unit?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onGetCountOrderType']);
    #endregion

    #region Store Receiving Inventory Item Cache
    Route::post('v1/store/receive-inventory-item-cache/create/{store_code}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemCacheController::class, 'onCreate']);
    Route::get('v1/store/receive-inventory-item-cache/scanning/current/get/{reference_number}/{receive_type}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemCacheController::class, 'onGetCurrentScanning']);
    Route::post('v1/store/receive-inventory-item-cache/current', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemCacheController::class, 'onGetCurrent']);
    Route::post('v1/store/receive-inventory-item-cache/delete/{reference_number}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemCacheController::class, 'onDelete']);
    #endregion

    #region Stock Inventory
    Route::get('v1/stock/inventory/get/{store_code}/{sub_unit?}', [App\Http\Controllers\v1\Stock\StockInventoryController::class, 'onGet']);
    Route::get('v1/stock/inventory/id/get/{stock_inventory_id?}', [App\Http\Controllers\v1\Stock\StockInventoryController::class, 'onGetById']);
    #endregion

    #region Stock Log
    Route::get('v1/stock/log/get/{store_code}/{item_code}/{sub_unit?}', [App\Http\Controllers\v1\Stock\StockLogController::class, 'onGet']);
    Route::get('v1/stock/log/details/get/{item_code}', [App\Http\Controllers\v1\Stock\StockLogController::class, 'onGetStockDetails']);
    #endregion

    #region Stock Transfer
    Route::post('v1/stock/transfer/create', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onCreate']);
    Route::post('v1/stock/transfer/cancel/{id}', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onCancel']);
    Route::get('v1/stock/transfer/current/get/{status}/{store_code}/{sub_unit?}', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onGetCurrent']);
    Route::get('v1/stock/transfer/get/{id}', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onGetById']);
    Route::post('v1/stock/transfer/pickup/{id}', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onPickupTransfer']);
    #endregion

    #region Stock Inventory Count
    Route::get('v1/stock/inventory-count/current/get/{status}/{store_code}/{store_sub_unit_short_name?}', [App\Http\Controllers\v1\Stock\StockInventoryCountController::class, 'onGet']);
    Route::get('v1/stock/inventory-count/department/get/{store_code}/{store_sub_unit_short_name?}', [App\Http\Controllers\v1\Stock\StockInventoryCountController::class, 'onGetItemByDepartment']);
    #endregion

    #region Stock Conversion
    Route::post('v1/stock/conversion/create/{stock_inventory_id}', [App\Http\Controllers\v1\Stock\StockConversionController::class, 'onCreate']);
    #endregion

    #region Customer Returns
    Route::post('v1/customer/return/form/create', [App\Http\Controllers\v1\Customer\CustomerReturnFormController::class, 'onCreate']);
    Route::get('v1/customer/return/form/current/get/{store_code}/{store_sub_unit_short_name?}', [App\Http\Controllers\v1\Customer\CustomerReturnFormController::class, 'onGetCurrent']);
    #endregion

    #region Customer Returns Item
    Route::get('v1/customer/return/item/get/{customer_return_form_id}', [App\Http\Controllers\v1\Customer\CustomerReturnItemController::class, 'onGetById']);
    #endregion

    #region Direct Purchase
    Route::get('v1/direct/purchase/current/get/{status}/{store_code}/{sub_unit?}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseController::class, 'onGetCurrent']);
    Route::get('v1/direct/purchase/get/{direct_purchase_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseController::class, 'onGetById']);
    Route::post('v1/direct/purchase/create', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseController::class, 'onCreate']);
    Route::post('v1/direct/purchase/close/{direct_purchase_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseController::class, 'onClose']);
    Route::post('v1/direct/purchase/update/{direct_purchase_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseController::class, 'onUpdateDirectPurchaseDetails']);
    Route::post('v1/direct/purchase/cancel/{direct_purchase_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseController::class, 'onCancel']);

    #endregion
    #region Direct Purchase Template
    Route::get('v1/direct/purchase/template/get/{store_code}/{sub_unit_short_name}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseTemplateController::class, 'onGet']);
    #endregion

    #region Direct Purchase Items
    Route::post('v1/direct/purchase/items/create', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseItemController::class, 'onCreate']);
    Route::post('v1/direct/purchase/items/update/{direct_purchase_item_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseItemController::class, 'onUpdate']);
    Route::post('v1/direct/purchase/items/delete/{direct_purchase_item_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseItemController::class, 'onDelete']);
    #endregion

    #region Purchase Request
    Route::post('v1/purchase/request/create', [App\Http\Controllers\v1\PurchaseRequest\PurchaseRequestController::class, 'onCreate']);
    Route::get('v1/purchase/request/current/get/{status}/{store_code}/{sub_unit?}', [App\Http\Controllers\v1\PurchaseRequest\PurchaseRequestController::class, 'onGetCurrent']);
    Route::get('v1/purchase/request/get/{purchase_request_id}', [App\Http\Controllers\v1\PurchaseRequest\PurchaseRequestController::class, 'onGetById']);
    Route::post('v1/purchase/request/update/{purchase_request_id}', [App\Http\Controllers\v1\PurchaseRequest\PurchaseRequestController::class, 'onUpdate']);
    Route::post('v1/purchase/request/cancel/{purchase_request_id}', [App\Http\Controllers\v1\PurchaseRequest\PurchaseRequestController::class, 'onCancel']);
    #endregion

    #region Purchase Request Template
    Route::get('v1/purchase/request/template/get/{store_code}/{sub_unit_short_name}', [App\Http\Controllers\v1\PurchaseRequest\PurchaseRequestTemplateController::class, 'onGet']);
    #endregion

    #region Direct Purchase Handled Items
    Route::post('v1/direct/purchase/handled-items/create', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseHandledItemController::class, 'onCreate']);
    Route::post('v1/direct/purchase/handled-items/delete/{direct_purchase_handled_item_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseHandledItemController::class, 'onDelete']);
    Route::post('v1/direct/purchase/handled-items/update/{direct_purchase_handled_item_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseHandledItemController::class, 'onUpdate']);
    Route::post('v1/direct/purchase/handled-items/post/{direct_purchase_handled_item_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseHandledItemController::class, 'onPost']);
    Route::get('v1/direct/purchase/handled-items/get/{direct_purchase_handled_item_id}', [App\Http\Controllers\v1\DirectPurchase\DirectPurchaseHandledItemController::class, 'onGetById']);
    #endregion

    #region Stock Out
    Route::post('v1/stock/out/create', [App\Http\Controllers\v1\Stock\StockOutController::class, 'onCreate']);
    Route::get('v1/stock/out/get/{store_code}/{store_sub_unit_short_name?}', [App\Http\Controllers\v1\Stock\StockOutController::class, 'onGet']);
    #endregion

    #region Stock Out Item
    Route::get('v1/stock/out-item/get/{stock_out_id}', [App\Http\Controllers\v1\Stock\StockOutItemController::class, 'onGet']);
    #endregion

    #region Stock Return Item
    Route::post('v1/stock/return-item/create', [App\Http\Controllers\v1\Stock\StockReturnItemController::class, 'onCreate']);
    #endregion

    #region Stock Inventory Count Template
    Route::get('v1/stock/inventory-count/template/get/{store_code}/{sub_unit_short_name}', [App\Http\Controllers\v1\Stock\StockInventoryCountTemplateController::class, 'onGet']);
    #endregion
});