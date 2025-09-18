<?php

namespace App\Console\Commands\CronJob\Store;

use App\Models\Store\StoreConsolidationCacheModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\Store\StoreReceivingInventoryModel;
use Http;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use DB;
use Carbon\Carbon;
class CreateStoreReceivingInventoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-store-receiving-inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Creating store receiving inventory from consolidation cache...');
        $this->onCreateStoreReceivingInventory();
    }

    protected function onCreateStoreReceivingInventory()
    {
        try {
            $storeConsolidationCache = StoreConsolidationCacheModel::where('status', 0)->first();
            if (!$storeConsolidationCache) {
                Log::info('No pending consolidation cache found.');
                return;
            }
            DB::beginTransaction();
            $createdByName = $storeConsolidationCache->created_by_name;
            $createdById = $storeConsolidationCache->created_by_id;
            $consolidatedData = json_decode($storeConsolidationCache->consolidated_data, true);
            $consolidatedOrderId = $consolidatedData['consolidated_order_id'];
            $warehouseCode = $consolidatedData['warehouse_code'];
            $insertData = [];

            $generatedReferenceNumber = StoreReceivingInventoryModel::onGenerateReferenceNumber($consolidatedOrderId);
            $storeReceivingInventory = StoreReceivingInventoryModel::create([
                'consolidated_order_id' => $consolidatedOrderId,
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $consolidatedData['warehouse_name'],
                'reference_number' => $generatedReferenceNumber,
                'delivery_date' => $consolidatedData['delivery_date'],
                'delivery_type' => $consolidatedData['delivery_type'],
                'created_by_name' => $createdByName,
                'created_by_id' => $createdById,
                'updated_by_id' => $createdById,
            ]);

            foreach ($consolidatedData['sessions'] as $storeOrders) {
                $storeCode = $storeOrders['store_code'];
                $storeName = $storeOrders['store_name'];
                $deliveryDate = $storeOrders['delivery_date'];
                $deliveryType = $storeOrders['delivery_type'];
                $orderDate = $storeOrders['order_date'];
                $orderSessionId = $storeOrders['order_session_id'] ?? null;
                // $storeSubUnitId = $storeOrders['store_sub_unit_id'];
                $storeSubUnitShortName = $storeOrders['store_sub_unit_short_name'];
                $storeSubUnitLongName = $storeOrders['store_sub_unit_long_name'];
                $orderReferenceNumber = isset($storeOrders['order_session_id']) ? 'CO-' . $storeOrders['order_session_id'] : $storeOrders['reference_number'];

                $exists = StoreReceivingInventoryItemModel::where('reference_number', $orderReferenceNumber)->exists();

                if ($exists) {
                    throw new Exception('Reference number already exists: ' . $orderReferenceNumber);
                }
                if (isset($storeOrders['ordered_items'])) {
                    foreach ($storeOrders['ordered_items'] as $orderedItems) {
                        $insertData[] = [
                            'store_receiving_inventory_id' => $storeReceivingInventory->id,
                            'order_type' => $orderedItems['order_type'] ?? 0,
                            'reference_number' => $orderReferenceNumber,
                            'store_code' => $storeCode,
                            'store_name' => $storeName,
                            'delivery_date' => $deliveryDate,
                            'delivery_type' => $deliveryType,
                            'order_date' => $orderDate,
                            'item_code' => $orderedItems['item_code'],
                            'item_description' => $orderedItems['item_description'],
                            'item_category_name' => $orderedItems['item_category_name'],
                            'order_quantity' => $orderedItems['order_quantity'],
                            'allocated_quantity' => $orderedItems['allocated_quantity'],
                            'fan_out_category' => $orderedItems['fan_out_category'] ?? null,
                            'order_session_id' => $orderSessionId,
                            // 'store_sub_unit_id' => $storeSubUnitId,
                            'store_sub_unit_short_name' => $storeSubUnitShortName,
                            'store_sub_unit_long_name' => $storeSubUnitLongName,
                            'received_quantity' => 0,
                            'received_items' => json_encode([]),
                            'type' => $consolidatedData['movement_type'] ?? 0, // Order
                            'created_by_id' => $createdById,
                            'created_by_name' => $createdByName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Bulk insert to speed up
            if (!empty($insertData)) {
                StoreReceivingInventoryItemModel::insert($insertData);
                Http::post(env('MGIOS_URL') . '/store-inventory-data/update/' . $consolidatedOrderId);
            }

            DB::commit();
            $storeConsolidationCache->status = 1; // Mark as processed
            $storeConsolidationCache->save();
            Log::info('Store receiving inventory created successfully from consolidation cache. ' . $consolidatedOrderId);
            return;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Failed to create store receiving inventory', [
                'error' => $exception->getMessage()
            ]);
            return;
        }
    }
}
