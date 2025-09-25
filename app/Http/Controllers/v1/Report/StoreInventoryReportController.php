<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockConversionModel;
use App\Models\Stock\StockInventoryCountModel;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Models\Stock\StockOutModel;
use App\Models\Stock\StockTransferModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use Throwable;

class StoreInventoryReportController extends Controller
{
    use ResponseTrait;
    public function onGenerateDailyMovementReport(Request $request)
    {
        try {
            $storeCode = $request->store_code ?? null; // Expected format: ['C001','C002']
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $transactionDate = $request->transaction_date ?? null; // Expected format: 'YYYY-MM-DD'
            $isGroupByItemCategory = $request->is_group_by_item_category ?? null; // Expected values: 0 (false), 1 (true) For store receiving
            $isGroupByItemDescription = $request->is_group_by_item_description ?? null; // Expected values: 0 (false), 1 (true) For store receiving

            $isShowOnlyNonZeroVariance = $request->is_show_only_non_zero_variance ?? null; // Expected values: 0 (false), 1 (true) For store receiving

            $response = \Http::withHeaders([
                'x-api-key' => config('apikeys.scm_api_key'),
            ])->get(config('apiurls.scm.url') . config('apiurls.scm.public_reason_list_current_get') . '1');

            $foodChargeReasonList = [];
            if ($response->successful()) {
                $foodChargeReasonList = $response->json()['success']['data'] ?? [];
            }
            $storeInventoryModel = StockInventoryModel::select([
                'id',
                'store_code',
                'store_sub_unit_short_name',
                'item_code',
                'item_description',
                'item_category_name',
            ]);
            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $storeInventoryModel->whereIn('store_code', $storeCode);
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
                $beginningStock = $this->onGetBeginningStock($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);

                $deliveryTransferCount = $this->onGetDeliveryTransferCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);
                $firstDelivery = $deliveryTransferCount['1D'] ?? 0;
                $secondDelivery = $deliveryTransferCount['2D'] ?? 0;
                $thirdDelivery = $deliveryTransferCount['3D'] ?? 0;
                $transactionIn = $deliveryTransferCount['store_transfer_in'] ?? 0;

                $storeTransferOutCount = $this->onGetStockTransferCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName, $foodChargeReasonList);
                $transactionOut = $storeTransferOutCount['store_transfer_out'] ?? 0;
                $pulledOut = $storeTransferOutCount['pullout'] ?? 0;
                $foodCharge = $storeTransferOutCount['food_charge'] ?? 0;

                $stockConversionCount = $this->onGetConvertedStockCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);
                $convertOut = $stockConversionCount['convert_out'] ?? 0;
                $convertIn = $stockConversionCount['convert_in'] ?? [];
                if (count($convertIn) > 0) {
                    $convertInData = array_merge($convertInData, $convertIn);
                }

                $stockOutCount = $this->onGetStockOutCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);

                $t1 = $beginningStock + $firstDelivery + $secondDelivery + $thirdDelivery;
                $actualCount = StockInventoryCountModel::onGetActualCountEOD($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);
                $countedQuantity = $actualCount['counted_quantity'];
                $countedRemarks = $actualCount['remarks'];
                $reportData["$itemCode|$storeCode|$storeSubUnitShortName"] = [
                    'id' => $inventory->id,
                    'store_code' => $storeCode,
                    'store_name' => $inventory->formatted_store_name_label,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'item_code' => $itemCode,
                    'item_description' => $inventory->item_description,
                    'item_category_name' => $inventory->item_category_name,
                    'beginning_stock' => $beginningStock,
                    'first_delivery' => $firstDelivery,
                    'second_delivery' => $secondDelivery,
                    'third_delivery' => $thirdDelivery,
                    't1' => $t1,
                    'transaction_in' => $transactionIn,
                    'transaction_out' => $transactionOut,
                    'pulled_out' => $pulledOut,
                    'convert_out' => $convertOut,
                    'convert_in' => 0,
                    'sold' => $stockOutCount,
                    'food_charge' => $foodCharge,
                    'running_balance' => 0,
                    'actual_count' => $countedQuantity,
                    'remarks' => $countedRemarks,
                    'variance' => 0,
                ];
            }

            foreach ($reportData as $key => &$data) {
                $data['convert_in'] += $convertInData[$key]['quantity'] ?? 0;

                $t2 = $data['t1'] + $data['transaction_in'] - $data['transaction_out'] - $data['pulled_out'] - $data['convert_out'] + $data['convert_in'];
                $data['t2'] = $t2;

                $runningBalance = $t2 - $data['sold'] - $data['food_charge'];
                $data['running_balance'] = $runningBalance;

                $variance = $data['actual_count'] - $data['running_balance'];
                $data['variance'] = $variance;

                if ($isShowOnlyNonZeroVariance && $variance == 0) {
                    unset($reportData[$key]);
                }
            }
            unset($data);

            if ($isGroupByItemCategory && $isGroupByItemDescription) {
                // Sort by category first, then by description
                $reportData = collect($reportData)
                    ->sortBy(function ($item) {
                        return $item['item_category_name'] . '|' . $item['item_description'];
                    })
                    ->toArray();
            } elseif ($isGroupByItemCategory) {
                // Sort only by category
                $reportData = collect($reportData)
                    ->sortBy('item_category_name')
                    ->toArray();
            } elseif ($isGroupByItemDescription) {
                // Sort only by description
                $reportData = collect($reportData)
                    ->sortBy('item_description')
                    ->toArray();
            }

            return $this->dataResponse('success', 200, __('msg.record_found'), array_values($reportData));
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
                'received_quantity',
                'received_at',
            ])->where([
                        'item_code' => $itemCode,
                        'store_code' => $storeCode,
                    ])->whereDate('received_at', $transactionDate);
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
                    $deliveryTransferCount['store_transfer_in'] += $delivery->received_quantity;
                }
            }

            return $deliveryTransferCount;
        } catch (Exception $e) {
            throw new Exception('Error fetching delivery count: ' . $e->getMessage());
        }

    }

    public function onGetStockTransferCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName, $foodChargeReasonList)
    {
        try {
            $stockTransferCount = [
                'store_transfer_out' => 0,
                'pullout' => 0,
                'food_charge' => 0,
            ];

            $stockTransferModel = StockTransferModel::where([
                'store_code' => $storeCode,
            ])
                ->whereNotIn('status', [0, 1]) // Exclude cancelled transfers
                ->whereDate('logistics_picked_up_at', $transactionDate);
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
                    case 0 || 2: // Store Transfer || store warehouse store
                        $stockTransferCount['store_transfer_out'] += $filteredItems->sum('quantity');
                        break;
                    case 1: // Pull Out
                        if (in_array($transfer['remarks'], $foodChargeReasonList)) {
                            $stockTransferCount['food_charge'] += $filteredItems->sum('quantity');
                        } else {
                            $stockTransferCount['pullout'] += $filteredItems->sum('quantity');
                        }
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
                    if (!isset($convertedStockCount['convert_in']["$conversionItem->item_code|$storeCode|$storeSubUnitShortName"])) {
                        $convertedStockCount['convert_in']["$conversionItem->item_code|$storeCode|$storeSubUnitShortName"] = [];
                    }

                    $convertedStockCount['convert_in']["$conversionItem->item_code|$storeCode|$storeSubUnitShortName"] = [
                        'item_code' => $conversionItem->item_code,
                        'quantity' => $conversionItem->converted_quantity,
                    ];

                }
            }
            return $convertedStockCount;
        } catch (Exception $e) {
            throw new Exception('Error fetching converted stock count: ' . $e->getMessage());
        }
    }

    public function onGetStockOutCount($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName)
    {
        try {
            $stockOutCount = 0;
            $stockOutModel = StockOutModel::where([
                'store_code' => $storeCode,
            ])->whereDate('created_at', $transactionDate);
            if ($storeSubUnitShortName) {
                $stockOutModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $stockOutModel = $stockOutModel->get();

            foreach ($stockOutModel as $stockOut) {
                $stockOut->stockOutItems->where('item_code', $itemCode)->each(function ($stockOutItem) use (&$stockOutCount) {
                    $stockOutCount += $stockOutItem->quantity;
                });
            }

            return $stockOutCount;
        } catch (Exception $e) {
            throw new Exception('Error fetching stock out count: ' . $e->getMessage());
        }
    }

    public function onGetBeginningStock($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName)
    {
        try {
            $subTractedTransactionDate = \Carbon\Carbon::parse($transactionDate)->subDay()->toDateString();

            $stockInventoryCountModel = StockInventoryCountModel::whereDate('created_at', $subTractedTransactionDate)
                ->where('store_code', $storeCode);

            if ($storeSubUnitShortName) {
                $stockInventoryCountModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }

            $stockInventoryCountModel = $stockInventoryCountModel->orderBy('id', 'DESC')->first();

            if ($stockInventoryCountModel) {
                $stockInventoryItemCount = $stockInventoryCountModel
                    ->stockInventoryItemsCount()
                    ->select('counted_quantity')
                    ->where('item_code', $itemCode)
                    ->first();

                if ($stockInventoryItemCount) {
                    return $stockInventoryItemCount->counted_quantity;
                }
            }

            $stockLogBeginningStock = StockLogModel::onGetBeginningStock($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);
            return $stockLogBeginningStock;
        } catch (Throwable $e) {
            throw new Exception("Error fetching beginning stock: {$e->getMessage()}", 0, $e);
        }
    }
}

