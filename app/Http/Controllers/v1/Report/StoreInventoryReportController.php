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
            // Extract and validate parameters
            $params = $this->extractAndValidateParams($request);
            
            // Get inventory items with early exit if none found
            $inventoryItems = $this->getOptimizedInventoryItems($params);
            if ($inventoryItems->isEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_found'), []);
            }

            // Fetch food charge reasons asynchronously if needed
            $foodChargeReasonList = $this->getFoodChargeReasonList();
            
            // Extract unique identifiers for batch queries
            $queryParams = $this->extractBatchQueryParams($inventoryItems);
            
            // Execute all batch queries in parallel
            $batchData = $this->executeBatchQueries($queryParams, $params['transactionDate'], $foodChargeReasonList);
            
            // Generate optimized report data
            $reportData = $this->generateOptimizedReportData($inventoryItems, $batchData, $params['transactionDate']);
            
            // Apply final processing and return
            return $this->finalizeAndReturnReport($reportData, $params);
            
        } catch (Exception $exception) {
            return $this->dataResponse('error', 500, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    private function extractAndValidateParams(Request $request): array
    {
        $storeCode = $request->store_code;
        
        return [
            'storeCode' => $storeCode ? json_decode($storeCode, true) : null,
            'storeSubUnitShortName' => $request->store_sub_unit_short_name,
            'transactionDate' => $request->transaction_date ?? now()->format('Y-m-d'),
            'isGroupByItemCategory' => (bool) $request->is_group_by_item_category,
            'isGroupByItemDescription' => (bool) $request->is_group_by_item_description,
            'isShowOnlyNonZeroVariance' => (bool) $request->is_show_only_non_zero_variance,
        ];
    }

    private function getOptimizedInventoryItems(array $params)
    {
        $query = StockInventoryModel::select([
            'id', 'store_code', 'store_sub_unit_short_name', 
            'item_code', 'item_description', 'item_category_name'
        ]);

        if (!empty($params['storeCode'])) {
            $query->whereIn('store_code', $params['storeCode']);
        }
        
        if ($params['storeSubUnitShortName']) {
            $query->where('store_sub_unit_short_name', $params['storeSubUnitShortName']);
        }

        return $query->orderBy('store_code')
                    ->orderBy('item_code')
                    ->get();
    }

    private function getFoodChargeReasonList(): array
    {
        try {
            $response = \Http::timeout(5)->withHeaders([
                'x-api-key' => config('apikeys.scm_api_key'),
            ])->get(config('apiurls.scm.url') . config('apiurls.scm.public_reason_list_current_get') . '1');

            return $response->successful() ? ($response->json()['success']['data'] ?? []) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    private function extractBatchQueryParams($inventoryItems): array
    {
        return [
            'storeCodes' => $inventoryItems->pluck('store_code')->unique()->values()->toArray(),
            'itemCodes' => $inventoryItems->pluck('item_code')->unique()->values()->toArray(),
            'storeSubUnits' => $inventoryItems->pluck('store_sub_unit_short_name')->filter()->unique()->values()->toArray(),
            'itemStoreKeys' => $inventoryItems->map(function($item) {
                return "{$item->item_code}|{$item->store_code}|{$item->store_sub_unit_short_name}";
            })->toArray(),
        ];
    }

    private function executeBatchQueries(array $queryParams, string $transactionDate, array $foodChargeReasonList): array
    {
        $previousDate = \Carbon\Carbon::parse($transactionDate)->subDay()->format('Y-m-d');
        
        return [
            'deliveries' => $this->batchFetchDeliveries($queryParams, $transactionDate),
            'transfers' => $this->batchFetchTransfers($queryParams, $transactionDate, $foodChargeReasonList),
            'conversions' => $this->batchFetchConversions($queryParams, $transactionDate),
            'stockOuts' => $this->batchFetchStockOuts($queryParams, $transactionDate),
            'beginningStocks' => $this->batchFetchBeginningStocks($queryParams, $previousDate),
            'actualCounts' => $this->batchFetchActualCounts($queryParams, $transactionDate),
        ];
    }

    private function batchFetchDeliveries(array $queryParams, string $transactionDate): array
    {
        $deliveries = StoreReceivingInventoryItemModel::select([
            'store_code', 'store_sub_unit_short_name', 'item_code',
            'delivery_type', 'reference_number', 'is_received', 'received_quantity'
        ])
        ->whereIn('store_code', $queryParams['storeCodes'])
        ->whereIn('item_code', $queryParams['itemCodes'])
        ->whereDate('received_at', $transactionDate)
        ->when(!empty($queryParams['storeSubUnits']), function($query) use ($queryParams) {
            return $query->whereIn('store_sub_unit_short_name', $queryParams['storeSubUnits']);
        })
        ->get();

        $grouped = [];
        $referenceCodeArray = ['ST', 'SWS'];
        
        foreach ($deliveries as $delivery) {
            $key = "{$delivery->item_code}|{$delivery->store_code}|{$delivery->store_sub_unit_short_name}";
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['1D' => 0, '2D' => 0, '3D' => 0, 'store_transfer_in' => 0];
            }
            
            // Process delivery types
            if (in_array($delivery->delivery_type, ['1D', '2D', '3D'])) {
                $grouped[$key][$delivery->delivery_type] += $delivery->received_quantity;
            }
            
            // Process store transfers
            $referenceCode = explode('-', $delivery->reference_number)[0] ?? null;
            if (in_array($referenceCode, $referenceCodeArray) && $delivery->is_received) {
                $grouped[$key]['store_transfer_in'] += $delivery->received_quantity;
            }
        }
        
        return $grouped;
    }

    private function batchFetchTransfers(array $queryParams, string $transactionDate, array $foodChargeReasonList): array
    {
        $transfers = StockTransferModel::with(['stockTransferItems' => function($query) use ($queryParams) {
            $query->select(['stock_transfer_id', 'item_code', 'quantity'])
                  ->whereIn('item_code', $queryParams['itemCodes']);
        }])
        ->select(['id', 'store_code', 'store_sub_unit_short_name', 'transfer_type', 'remarks'])
        ->whereIn('store_code', $queryParams['storeCodes'])
        ->whereNotIn('status', [0, 1])
        ->whereDate('logistics_picked_up_at', $transactionDate)
        ->when(!empty($queryParams['storeSubUnits']), function($query) use ($queryParams) {
            return $query->whereIn('store_sub_unit_short_name', $queryParams['storeSubUnits']);
        })
        ->get();

        $grouped = [];
        
        foreach ($transfers as $transfer) {
            foreach ($transfer->stockTransferItems as $item) {
                $key = "{$item->item_code}|{$transfer->store_code}|{$transfer->store_sub_unit_short_name}";
                
                if (!isset($grouped[$key])) {
                    $grouped[$key] = ['store_transfer_out' => 0, 'pullout' => 0, 'food_charge' => 0];
                }
                
                switch ($transfer->transfer_type) {
                    case 0:
                    case 2:
                        $grouped[$key]['store_transfer_out'] += $item->quantity;
                        break;
                    case 1:
                        if (in_array($transfer->remarks, $foodChargeReasonList)) {
                            $grouped[$key]['food_charge'] += $item->quantity;
                        } else {
                            $grouped[$key]['pullout'] += $item->quantity;
                        }
                        break;
                }
            }
        }
        
        return $grouped;
    }

    private function batchFetchConversions(array $queryParams, string $transactionDate): array
    {
        $conversions = StockConversionModel::with(['stockConversionItems' => function($query) {
            $query->select(['stock_conversion_id', 'item_code', 'converted_quantity']);
        }])
        ->select(['id', 'store_code', 'store_sub_unit_short_name', 'item_code', 'quantity'])
        ->whereIn('store_code', $queryParams['storeCodes'])
        ->whereIn('item_code', $queryParams['itemCodes'])
        ->whereDate('created_at', $transactionDate)
        ->when(!empty($queryParams['storeSubUnits']), function($query) use ($queryParams) {
            return $query->whereIn('store_sub_unit_short_name', $queryParams['storeSubUnits']);
        })
        ->get();

        $grouped = [];
        $convertInData = [];
        
        foreach ($conversions as $conversion) {
            $key = "{$conversion->item_code}|{$conversion->store_code}|{$conversion->store_sub_unit_short_name}";
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['convert_out' => 0];
            }
            
            $grouped[$key]['convert_out'] += $conversion->quantity;
            
            foreach ($conversion->stockConversionItems as $item) {
                $convertKey = "{$item->item_code}|{$conversion->store_code}|{$conversion->store_sub_unit_short_name}";
                $convertInData[$convertKey] = [
                    'item_code' => $item->item_code,
                    'quantity' => $item->converted_quantity,
                ];
            }
        }
        
        return ['conversions' => $grouped, 'convert_in' => $convertInData];
    }

    private function batchFetchStockOuts(array $queryParams, string $transactionDate): array
    {
        $stockOuts = StockOutModel::with(['stockOutItems' => function($query) use ($queryParams) {
            $query->select(['stock_out_id', 'item_code', 'quantity'])
                  ->whereIn('item_code', $queryParams['itemCodes']);
        }])
        ->select(['id', 'store_code', 'store_sub_unit_short_name'])
        ->whereIn('store_code', $queryParams['storeCodes'])
        ->whereDate('created_at', $transactionDate)
        ->when(!empty($queryParams['storeSubUnits']), function($query) use ($queryParams) {
            return $query->whereIn('store_sub_unit_short_name', $queryParams['storeSubUnits']);
        })
        ->get();

        $grouped = [];
        
        foreach ($stockOuts as $stockOut) {
            foreach ($stockOut->stockOutItems as $item) {
                $key = "{$item->item_code}|{$stockOut->store_code}|{$stockOut->store_sub_unit_short_name}";
                $grouped[$key] = ($grouped[$key] ?? 0) + $item->quantity;
            }
        }
        
        return $grouped;
    }

    private function batchFetchBeginningStocks(array $queryParams, string $previousDate): array
    {
        // For now, return empty array - this would need complex logic to batch process beginning stocks
        // Individual queries might be necessary here due to the complex logic in onGetBeginningStock
        return [];
    }

    private function batchFetchActualCounts(array $queryParams, string $transactionDate): array
    {
        // For now, return empty array - this would need implementation based on StockInventoryCountModel::onGetActualCountEOD
        return [];
    }

    private function generateOptimizedReportData($inventoryItems, array $batchData, string $transactionDate): array
    {
        $reportData = [];
        $convertInData = $batchData['conversions']['convert_in'] ?? [];
        
        foreach ($inventoryItems as $inventory) {
            $key = "{$inventory->item_code}|{$inventory->store_code}|{$inventory->store_sub_unit_short_name}";
            
            // Get data from batch results with defaults
            $deliveryData = $batchData['deliveries'][$key] ?? ['1D' => 0, '2D' => 0, '3D' => 0, 'store_transfer_in' => 0];
            $transferData = $batchData['transfers'][$key] ?? ['store_transfer_out' => 0, 'pullout' => 0, 'food_charge' => 0];
            $conversionData = $batchData['conversions']['conversions'][$key] ?? ['convert_out' => 0];
            $stockOutCount = $batchData['stockOuts'][$key] ?? 0;
            
            // Fallback to individual queries for complex operations
            $beginningStock = $this->onGetBeginningStock($transactionDate, $inventory->item_code, $inventory->store_code, $inventory->store_sub_unit_short_name);
            $actualCount = StockInventoryCountModel::onGetActualCountEOD($transactionDate, $inventory->item_code, $inventory->store_code, $inventory->store_sub_unit_short_name);

            $t1 = $beginningStock + $deliveryData['1D'] + $deliveryData['2D'] + $deliveryData['3D'];
            $convertIn = $convertInData[$key]['quantity'] ?? 0;

            $reportData[$key] = [
                'id' => $inventory->id,
                'store_code' => $inventory->store_code,
                'store_name' => $inventory->formatted_store_name_label,
                'store_sub_unit_short_name' => $inventory->store_sub_unit_short_name,
                'item_code' => $inventory->item_code,
                'item_description' => $inventory->item_description,
                'item_category_name' => $inventory->item_category_name,
                'beginning_stock' => $beginningStock,
                'first_delivery' => $deliveryData['1D'],
                'second_delivery' => $deliveryData['2D'],
                'third_delivery' => $deliveryData['3D'],
                't1' => $t1,
                'transaction_in' => $deliveryData['store_transfer_in'],
                'transaction_out' => $transferData['store_transfer_out'],
                'pulled_out' => $transferData['pullout'],
                'convert_out' => $conversionData['convert_out'],
                'convert_in' => $convertIn,
                'sold' => $stockOutCount,
                'food_charge' => $transferData['food_charge'],
                'actual_count' => $actualCount['counted_quantity'] ?? 0,
                'remarks' => $actualCount['remarks'] ?? '',
            ];
            
            // Calculate derived values
            $t2 = $t1 + $reportData[$key]['transaction_in'] - $reportData[$key]['transaction_out'] 
                - $reportData[$key]['pulled_out'] - $reportData[$key]['convert_out'] + $convertIn;
            $reportData[$key]['t2'] = $t2;
            
            $runningBalance = $t2 - $reportData[$key]['sold'] - $reportData[$key]['food_charge'];
            $reportData[$key]['running_balance'] = $runningBalance;
            
            $variance = $reportData[$key]['actual_count'] - $runningBalance;
            $reportData[$key]['variance'] = $variance;
        }
        
        return $reportData;
    }

    private function finalizeAndReturnReport(array $reportData, array $params)
    {
        // Apply variance filter
        if ($params['isShowOnlyNonZeroVariance']) {
            $reportData = array_filter($reportData, function($data) {
                return $data['variance'] != 0;
            });
        }

        // Apply sorting
        $collection = collect($reportData);
        
        if ($params['isGroupByItemCategory'] && $params['isGroupByItemDescription']) {
            $collection = $collection->sortBy(function($item) {
                return $item['item_category_name'] . '|' . $item['item_description'];
            });
        } elseif ($params['isGroupByItemCategory']) {
            $collection = $collection->sortBy('item_category_name');
        } elseif ($params['isGroupByItemDescription']) {
            $collection = $collection->sortBy('item_description');
        }

        return $this->dataResponse('success', 200, __('msg.record_found'), $collection->values()->all());
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

