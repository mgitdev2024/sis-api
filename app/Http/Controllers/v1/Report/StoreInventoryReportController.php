<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockConversionModel;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Models\Stock\StockTransferModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;

class StoreInventoryReportController extends Controller
{
    use ResponseTrait;
    public function onGenerateDailyMovementReport(Request $request)
    {
        try {
            $storeCode = $request->store_code ?? null;
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $transactionDate = $request->transaction_date ?? null; // Expected format: 'YYYY-MM-DD'
            $isReceived = $request->is_received ?? null; // Expected values: 0 (Pending), 1 (Received) For store receiving

            $storeInventoryModel = StockInventoryModel::select([
                'store_code',
                'store_sub_unit_short_name',
                'item_code',
                'item_description',
                'item_category_name',
            ]);
            if ($storeCode) {
                $storeInventoryModel->where('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $storeInventoryModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }

            $storeInventoryModel = $storeInventoryModel->get();

            $reportData = [];
            $convertInData = [];
            foreach ($storeInventoryModel as $inventory) {
                $itemCode = $inventory->item_code;
                $storeCode = $inventory->store_code;
                $storeSubUnitShortName = $inventory->store_sub_unit_short_name ?? null;
                $beginningStock = StockLogModel::onGetBeginningStock($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);

                $deliveryTransferCount = $this->onGetDeliveryTransferCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);
                $firstDelivery = $deliveryTransferCount['1D'] ?? 0;
                $secondDelivery = $deliveryTransferCount['2D'] ?? 0;
                $thirdDelivery = $deliveryTransferCount['3D'] ?? 0;
                $transactionIn = $deliveryTransferCount['store_transfer_in'] ?? 0;

                $storeTransferOutCount = $this->onGetStockTransferCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);
                $transactionOut = $storeTransferOutCount['store_transfer_out'] ?? 0;
                $pulledOut = $storeTransferOutCount['pullout'] ?? 0;

                $stockConversionCount = $this->onGetConvertedStockCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);
                $convertOut = $stockConversionCount['convert_out'] ?? 0;
                $convertIn = $stockConversionCount['convert_in'] ?? [];
                if (count($convertIn) > 0) {
                    array_merge($convertInData, $stockConversionCount['convert_in']);
                }

                $reportData[$itemCode] = [
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'item_code' => $itemCode,
                    'item_description' => $inventory->item_description,
                    'item_category_name' => $inventory->item_category_name,
                    'beginning_stock' => $beginningStock,
                    'first_delivery' => $firstDelivery,
                    'second_delivery' => $secondDelivery,
                    'third_delivery' => $thirdDelivery,
                    'transaction_in' => $transactionIn,
                    'transaction_out' => $transactionOut,
                    'pulled_out' => $pulledOut,
                    'convert_out' => $convertOut,
                    'convert_in' => 0,
                ];
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $reportData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetDeliveryTransferCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName)
    {
        try {
            $deliveryTransferCount = [
                '1D' => 0,
                '2D' => 0,
                '3D' => 0,
                'store_transfer_in' => 0,
            ];

            $storeReceivingInventoryItemModel = StoreReceivingInventoryItemModel::select([
                'delivery_type',
                'reference_number',
                'is_received',
                'received_quantity'
            ])->where([
                        'item_code' => $itemCode,
                        'store_code' => $storeCode,
                    ])->whereDate('created_at', $transactionDate);
            if ($storeSubUnitShortName) {
                $storeReceivingInventoryItemModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $storeReceivingInventoryItemModel = $storeReceivingInventoryItemModel->get();

            $referenceCodeArray = ['ST', 'SWS'];
            foreach ($storeReceivingInventoryItemModel as $delivery) {
                switch ($delivery->delivery_type) {
                    case '1D':
                        $deliveryTransferCount['1D'] += $delivery->received_quantity;
                        break;
                    case '2D':
                        $deliveryTransferCount['2D'] += $delivery->received_quantity;
                        break;
                    case '3D':
                        $deliveryTransferCount['3D'] += $delivery->received_quantity;
                        break;
                }
                $referenceNumber = explode('-', $delivery->reference_number);
                $referenceCode = $referenceNumber[0] ?? null;
                if (in_array($referenceCode, $referenceCodeArray) && $delivery->is_received) {
                    $deliveryTransferCount['store_transfer_in']++;
                }
            }

            return $deliveryTransferCount;
        } catch (Exception $e) {
            throw new Exception('Error fetching delivery count: ' . $e->getMessage());
        }

    }

    public function onGetStockTransferCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName)
    {
        try {
            $stockTransferCount = [
                'store_transfer_out' => 0,
                'pullout' => 0,
            ];

            $stockTransferModel = StockTransferModel::select([
                'transfer_type',
            ])->where([
                        'store_code' => $storeCode,
                    ])
                ->whereNotIn('status', [0, 1]) // Exclude cancelled transfers
                ->whereDate('created_at', $transactionDate);
            if ($storeSubUnitShortName) {
                $stockTransferModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $stockTransferModel = $stockTransferModel->get();

            foreach ($stockTransferModel as $transfer) {
                $filteredItems = $transfer->stockTransferItems->where('item_code', $itemCode);
                if ($filteredItems->isEmpty()) {
                    continue; // Skip if no items match the item code
                }

                switch ($transfer->transfer_type) {
                    case 0: // Store Transfer
                        $stockTransferCount['store_transfer_out'] += $filteredItems->sum('quantity');
                        break;
                    case 1: // Pull Out
                        $stockTransferCount['pullout'] += $filteredItems->sum('quantity');
                        break;
                }
            }
            return $stockTransferCount;
        } catch (Exception $e) {
            throw new Exception('Error fetching stock transfer count: ' . $e->getMessage());
        }
    }

    public function onGetConvertedStockCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName)
    {
        try {
            $convertedStockCount = [
                'convert_in' => [],
                'convert_out' => 0,
            ];

            $stockConversionModel = StockConversionModel::where([
                'item_code' => $itemCode,
                'store_code' => $storeCode,
            ])->whereDate('created_at', $transactionDate);
            if ($storeSubUnitShortName) {
                $stockConversionModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $stockConversionModel = $stockConversionModel->get();

            foreach ($stockConversionModel as $conversion) {
                $convertedStockCount['convert_out'] += $conversion->quantity;
                foreach ($conversion->stockConversionItems as $conversionItem) {
                    if (!isset($convertedStockCount['convert_in'][$storeCode])) {
                        $convertedStockCount['convert_in'][$storeCode] = [];
                    }

                    $convertedStockCount['convert_in'][$storeCode][] = [
                        'item_code' => $conversionItem->item_code,
                        'quantity' => $conversionItem->quantity,
                    ];
                }
            }
            return $convertedStockCount;
        } catch (Exception $e) {
            throw new Exception('Error fetching converted stock count: ' . $e->getMessage());
        }
    }
}
