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
// Route::get('v1/check/token/{token}', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onCheckToken']);


Route::post('v1/store/receive-inventory/{is_internal?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryController::class, 'onCreate']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('v1/check/token', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onCheckToken']); // Logout
    Route::get('v1/logout', [App\Http\Controllers\v1\Auth\CredentialController::class, 'onLogout']); // Logout

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
    #endregion

    #region System Status
    Route::post('v1/system/admin/status/change/{system_id}', [App\Http\Controllers\v1\Admin\System\SCMSystemController::class, 'onChangeStatus']);
    Route::get('v1/system/admin/get/{system_id?}', [App\Http\Controllers\v1\Admin\System\SCMSystemController::class, 'onGet']);
    #endregion
});

Route::group(['middleware' => ['auth:sanctum', 'check.system.status:SIS']], function () {
    #region Store Receiving Inventory
    Route::get('v1/store/receive-inventory/current/get/{status}/{store_code?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryController::class, 'onGetCurrent']);
    Route::get('v1/store/receive-inventory/get/{store_receiving_inventory_id}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryController::class, 'onGetById']);
    #endregion

    #region Store Receiving Inventory Item
    Route::get('v1/store/receive-inventory-item/current/get/{store_code}/{status?}/{reference_number?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onGetCurrent']);
    Route::get('v1/store/receive-inventory-item/manual/get/{reference_number}/{selected_item_codes}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onGetCheckedManual']);

    Route::get('v1/store/receive-inventory-item/category/get/{store_code}/{status?}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onGetCategory']);
    Route::post('v1/store/receive-inventory-item/scan/{store_code}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onScanItems']);
    Route::post('v1/store/receive-inventory-item/complete/{reference_number}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemController::class, 'onComplete']);
    #endregion

    #region Store Receiving Inventory Item Cache
    Route::post('v1/store/receive-inventory-item-cache/create/{store_code}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemCacheController::class, 'onCreate']);
    Route::get('v1/store/receive-inventory-item-cache/current/get/{reference_number}/{receive_type}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemCacheController::class, 'onGetCurrent']);
    Route::post('v1/store/receive-inventory-item-cache/delete/{reference_number}', [App\Http\Controllers\v1\Store\StoreReceivingInventoryItemCacheController::class, 'onDelete']);
    #endregion

    #region Stock Inventory0
    Route::get('v1/stock/inventory/get/{is_group}/{store_code}/{sub_unit?}', [App\Http\Controllers\v1\Stock\StockInventoryController::class, 'onGet']);
    #endregion

    #region Stock Log
    Route::get('v1/stock/log/get/{store_code}/{item_code}/{sub_unit?}', [App\Http\Controllers\v1\Stock\StockLogController::class, 'onGet']);
    Route::get('v1/stock/log/details/get/{item_code}', [App\Http\Controllers\v1\Stock\StockLogController::class, 'onGetStockDetails']);
    #endregion

    #region Stock Transfer
    Route::post('v1/stock/transfer/create', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onCreate']);
    Route::post('v1/stock/transfer/cancel/{id}', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onCancel']);
    Route::get('v1/stock/transfer/current/get/{status}/{store_code}/{sub_unit?}', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onGet']);
    Route::get('v1/stock/transfer/get/{id}', [App\Http\Controllers\v1\Stock\StockTransferController::class, 'onGetById']);
    #endregion

    #region Stock Inventory Count
    Route::post('v1/stock/inventory-count/create', [App\Http\Controllers\v1\Stock\StockInventoryCountController::class, 'onCreate']);
    Route::get('v1/stock/inventory-count/current/get/{status}/{store_code}/{store_sub_unit_short_name?}', [App\Http\Controllers\v1\Stock\StockInventoryCountController::class, 'onGet']);
    #endregion

    #region Stock Inventory Count
    Route::get('v1/stock/inventory-item-count/current/get/{store_inventory_count_id}', [App\Http\Controllers\v1\Stock\StockInventoryItemCountController::class, 'onGetById']);
    Route::post('v1/stock/inventory-item-count/update/{store_inventory_count_id}', [App\Http\Controllers\v1\Stock\StockInventoryItemCountController::class, 'onUpdate']);
    Route::post('v1/stock/inventory-item-count/post/{store_inventory_count_id}', [App\Http\Controllers\v1\Stock\StockInventoryItemCountController::class, 'onPost']);
    #endregion

    #region Customer Returns
    Route::post('v1/customer/return/form/create', [App\Http\Controllers\v1\Customer\CustomerReturnFormController::class, 'onCreate']);
    Route::get('v1/customer/return/form/current/get/{store_code}/{store_sub_unit_short_name?}', [App\Http\Controllers\v1\Customer\CustomerReturnFormController::class, 'onGetCurrent']);
    #endregion

    #region Customer Returns Item
    Route::get('v1/customer/return/item/get/{customer_return_form_id}', [App\Http\Controllers\v1\Customer\CustomerReturnItemController::class, 'onGetById']);
    #endregion
});
