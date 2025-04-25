<?php

namespace App\Traits\Stock;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Models\Stock\StockReceivedItemModel;
use Exception;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

trait StockTrait
{
    use ResponseTrait;

    public function onCreateStockLogs($type, $storeCode, $storeSubUnitShortName, $createdById, $receiveType, $storeReceivingInventoryId, $transactionItems, $referenceNumber)
    {
        // Type = 'stock_in' or 'stock_out'
        /**
         * what to do:
         * the loop through the transaction items with reference numebr
         */
        try {
            switch ($type) {
                case 'stock_in':
                    if ($receiveType == 'scan') {
                        $this->onStoreReceivedItems($storeCode, $storeSubUnitShortName, $storeReceivingInventoryId, $transactionItems, $createdById);
                    }
                    $this->onStockUpdate($storeCode, $storeSubUnitShortName, $transactionItems, 'stock_in', $receiveType, $referenceNumber, $createdById);
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

    public function onStockUpdate($storeCode, $storeSubUnitShortName, $transactionItems, $type, $receiveType, $referenceNumber, $createdById)
    {
        try {
            // Stock Log Update
            if ($receiveType == 'scan' && $type == 'stock_in') {
                $this->onStockLog($transactionItems, $storeCode, $storeSubUnitShortName, $createdById, $referenceNumber, $receiveType);
                $this->onStockInventory($transactionItems, $storeCode, $storeSubUnitShortName, $createdById, $referenceNumber, $receiveType);

            } else if ($receiveType == 'manual' && $type == 'stock_in') {
                $this->onStockLog($transactionItems, $storeCode, $storeSubUnitShortName, $createdById, $referenceNumber, $receiveType);
                $this->onStockInventory($transactionItems, $storeCode, $storeSubUnitShortName, $createdById, $referenceNumber, $receiveType);
            }

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    private function onStockLog($transactionItems, $storeCode, $storeSubUnitShortName, $createdById, $referenceNumber, $receiveType)
    {
        $itemCode = array_key_first(collect($transactionItems)->groupBy('ic')->toArray());

        if ($receiveType == 'scan') {
            $itemQuantityCount = count($transactionItems);
        } else {
            $itemQuantityCount = $transactionItems[0]['q'];
        }

        $stockLogModel = StockLogModel::where([
            'store_code' => $storeCode,
            'store_sub_unit_short_name' => $storeSubUnitShortName,
            'item_code' => $itemCode,
        ])->orderBy('id', 'DESC')->first();
        $finalStock = $stockLogModel->final_stock ?? 0;
        $stockQuantity = $finalStock + $itemQuantityCount;
        $currentTransactionItems = $stockLogModel->transaction_items ?? '[]';
        $stockTransactionItems = array_merge(
            json_decode($currentTransactionItems ?? '[]', true),
            $transactionItems
        );
        $stockLogCreateModel = new StockLogModel();
        $stockLogCreateModel->reference_number = $referenceNumber;
        $stockLogCreateModel->store_code = $storeCode;
        $stockLogCreateModel->store_sub_unit_short_name = $storeSubUnitShortName;
        $stockLogCreateModel->item_code = $itemCode;
        $stockLogCreateModel->transaction_items = $receiveType == 'scan' ? json_encode($stockTransactionItems) : null;
        $stockLogCreateModel->transaction_type = 'in';
        $stockLogCreateModel->transaction_sub_type = 'received';
        $stockLogCreateModel->quantity = $itemQuantityCount;
        $stockLogCreateModel->initial_stock = $finalStock;
        $stockLogCreateModel->final_stock = $stockQuantity;
        $stockLogCreateModel->created_by_id = $createdById;
        $stockLogCreateModel->save();
    }
    private function onStockInventory($transactionItems, $storeCode, $storeSubUnitShortName, $createdById, $referenceNumber, $receiveType)
    {
        $itemCode = array_key_first(collect($transactionItems)->groupBy('ic')->toArray());

        if ($receiveType == 'scan') {
            $itemQuantityCount = count($transactionItems);
        } else {
            $itemQuantityCount = $transactionItems[0]['q'];
        }

        $stockInventoryModel = StockInventoryModel::where([
            'store_code' => $storeCode,
            'store_sub_unit_short_name' => $storeSubUnitShortName,
            'item_code' => $itemCode,
        ])->first();

        if ($stockInventoryModel) {
            $stockInventoryModel->stock_count += $itemQuantityCount;
            $stockInventoryModel->save();
        } else {
            $stockInventoryCreateModel = new StockInventoryModel();
            $stockInventoryCreateModel->store_code = $storeCode;
            $stockInventoryCreateModel->store_sub_unit_short_name = $storeSubUnitShortName;
            $stockInventoryCreateModel->item_code = $itemCode;
            $stockInventoryCreateModel->stock_count = $itemQuantityCount;
            $stockInventoryCreateModel->created_by_id = $createdById;
            $stockInventoryCreateModel->save();
        }
    }
}

