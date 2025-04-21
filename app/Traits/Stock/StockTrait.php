<?php

namespace App\Traits\Stock;
use App\Models\Stock\StockReceivedItemModel;
use Exception;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

trait StockTrait
{
    use ResponseTrait;

    public function onCreateStockLogs($type, $storeCode, $storeSubUnitShortName, $createdById, $receiveType, $storeReceivingInventoryId = null, $transactionItems = null)
    {
        // Type = 'stock_in' or 'stock_out'
        try {
            switch ($type) {
                case 'stock_in':
                    if ($receiveType == 'scan') {
                        $this->onStoreReceivedItems($storeCode, $storeSubUnitShortName, $storeReceivingInventoryId, $transactionItems, $createdById);
                    }
                    $this->onStockUpdate($storeCode, $storeSubUnitShortName);
                    break;
                case 'stock_out':
                    break;
                default:
                    throw new Exception("Invalid stock type");
            }
            // **TODO:
            // Update Stock Logs
            // update stock Inventory
            // update stock received items to update location on each items
        } catch (Exception $exception) {
            dd($exception);
            throw new Exception($exception->getMessage());
        }
    }

    public function onStoreReceivedItems($storeCode, $storeSubUnitShortName, $storeReceivingInventoryId, $transactionItems, $createdById)
    {
        try {
            // Find the store and check if it is existing. If yes, update that, else create a new one
            foreach ($transactionItems as $items) {
                $itemCode = $items['ic'];
                $stockReceivedItems = StockReceivedItemModel::where([
                    'batch_id' => $items['bid'],
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                ])->first();

                if ($stockReceivedItems) {
                    $currentItems = json_decode($stockReceivedItems->received_items, true);
                    $currentItems[] = [
                        'ic' => $itemCode,
                        'bid' => $items['bid'],
                        'q' => $items['q'],
                        'sn' => $items['sn'],
                    ];
                    $stockReceivedItems->received_items = json_encode($currentItems);
                    $stockReceivedItems->save();
                } else {
                    $stockReceivedItems = new StockReceivedItemModel();
                    $stockReceivedItems->store_code = $storeCode;
                    $stockReceivedItems->store_sub_unit_short_name = $storeSubUnitShortName;
                    $stockReceivedItems->item_code = $itemCode;
                    $stockReceivedItems->batch_id = $items['bid'];
                    $stockReceivedItems->received_items = json_encode([
                        [
                            'ic' => $itemCode,
                            'bid' => $items['bid'],
                            'q' => $items['q'],
                            'sn' => $items['sn'],
                        ]
                    ]);
                    $stockReceivedItems->store_receiving_inventory_item_id = $storeReceivingInventoryId;
                    $stockReceivedItems->created_by_id = $createdById;
                    $stockReceivedItems->save();
                }
            }

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onStockUpdate()
    {

    }
}

