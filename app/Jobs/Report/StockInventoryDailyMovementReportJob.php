<?php

namespace App\Jobs\Report;

use App\Models\Stock\StockConversionModel;
use App\Models\Stock\StockInventoryCountModel;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockOutModel;
use App\Models\Stock\StockTransferModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Traits\Report\GeneratedReportDataTrait;
use Cache;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use DB;
use Exception;
use Log;
use Throwable;

class StockInventoryDailyMovementReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use GeneratedReportDataTrait;
    /**
     * Create a new job instance.
     */

    public $fields;

    public function __construct($fields)
    {
        $this->fields = $fields;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Store Inventory Daily Movement Report started.');
        $this->onGenerateDailyMovementReport($this->fields);
    }

    private function onGenerateDailyMovementReport($fields)
    {
        try {
            $storeCode = $fields['store_code'] ?? null;
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'] ?? null;
            $transactionDate = $fields['transaction_date'] ?? null;
            $isGroupByItemCategory = $fields['is_group_by_item_category'] ?? null;
            $isGroupByItemDescription = $fields['is_group_by_item_description'] ?? null;
            $isShowOnlyNonZeroVariance = $fields['is_show_only_non_zero_variance'] ?? null;
            $departmentId = $fields['department_id'] ?? null;
            $uuid = $fields['uuid'] ?? null;
            // Cache food charge reasons for 1 hour
            $foodChargeReasonList = Cache::remember('food_charge_reasons', 3600, function () {
                $response = Http::withHeaders([
                    'x-api-key' => config('apikeys.scm_api_key'),
                ])->get(config('apiurls.scm.url') . config('apiurls.scm.public_reason_list_current_get') . '1');

                return $response->successful() ? ($response->json()['success']['data'] ?? []) : [];
            });

            $departmentItemsResponse = Http::withHeaders([
                'x-api-key' => config('apikeys.mgios_api_key'),
            ])->post(
                config('apiurls.mgios.url') . config('apiurls.mgios.public_get_item_by_department_id'),
                ['department_ids' => $departmentId]
            );

            $departmentItems = $departmentItemsResponse->successful() ? ($departmentItemsResponse->json() ?? []) : [];
            // Get inventory items with eager loading
            $storeInventoryModel = StockInventoryModel::select([
                'id',
                'store_code',
                'store_sub_unit_short_name',
                'item_code',
                'item_description',
                'item_category_name',
                'uom',
                'is_sis_variant',
            ]);

            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $storeInventoryModel->whereIn('store_code', $storeCode);
            }

            if ($storeSubUnitShortName) {
                $storeInventoryModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }

            if (count($departmentItems) > 0) {
                $storeInventoryModel->whereIn('item_code', $departmentItems);
            }

            $inventoryItems = $storeInventoryModel->get();

            // Extract item codes and store info for bulk queries
            $itemCodes = $inventoryItems->pluck('item_code')->unique()->toArray();
            $storeCodes = $inventoryItems->pluck('store_code')->unique()->toArray();

            // Bulk fetch all data at once
            $bulkData = $this->getBulkData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName, $foodChargeReasonList);

            $reportData = [];
            $convertInData = [];

            foreach ($inventoryItems as $inventory) {
                $key = "{$inventory->item_code}|{$inventory->store_code}|{$inventory->store_sub_unit_short_name}";

                $beginningStock = $bulkData['beginning_stock'][$key] ?? 0;
                $deliveryData = $bulkData['delivery'][$key] ?? ['1D' => 0, '2D' => 0, '3D' => 0, 'store_transfer_in' => 0];
                $transferData = $bulkData['transfer'][$key] ?? ['store_transfer_out' => 0, 'pullout' => 0, 'food_charge' => 0];
                $conversionData = $bulkData['conversion'][$key] ?? ['convert_out' => 0, 'convert_in' => []];
                $stockOutCount = $bulkData['stock_out'][$key] ?? 0;
                $actualCount = $bulkData['actual_count'][$key] ?? ['counted_quantity' => 0, 'remarks' => ''];

                if (!empty($conversionData['convert_in'])) {
                    $convertInData = array_merge($convertInData, $conversionData['convert_in']);
                }

                $t1 = $beginningStock + $deliveryData['1D'] + $deliveryData['2D'] + $deliveryData['3D'];

                $reportData[$key] = [
                    'id' => $inventory->id,
                    'store_code' => $inventory->store_code,
                    'store_name' => $inventory->formatted_store_name_label,
                    'store_sub_unit_short_name' => $inventory->store_sub_unit_short_name,
                    'item_code' => $inventory->item_code,
                    'uom' => $inventory->uom,
                    'is_sis_variant' => $inventory->is_sis_variant,
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
                    'convert_in' => 0,
                    'sold' => $stockOutCount,
                    'food_charge' => $transferData['food_charge'],
                    'running_balance' => 0,
                    'actual_count' => $actualCount['counted_quantity'],
                    'remarks' => $actualCount['remarks'],
                    'variance' => 0,
                ];
            }

            // Calculate final values
            foreach ($reportData as $key => &$data) {
                $data['convert_in'] = $convertInData[$key]['quantity'] ?? 0;
                $t2 = $data['t1'] + $data['transaction_in'] - $data['transaction_out'] - $data['pulled_out'] - $data['convert_out'] + $data['convert_in'];
                $data['t2'] = $t2;
                $data['running_balance'] = $t2 - $data['sold'] - $data['food_charge'];
                $data['variance'] = $data['actual_count'] - $data['running_balance'];

                if ($isShowOnlyNonZeroVariance && $data['variance'] == 0) {
                    unset($reportData[$key]);
                }
            }

            // Apply sorting
            $reportData = $this->applySorting($reportData, $isGroupByItemCategory, $isGroupByItemDescription);

            $this->fillReportData($uuid, $reportData);
            Log::info('Store Inventory Daily Movement Report generated successfully.');
        } catch (Exception $exception) {
            Log::error('Error generating Store Inventory Daily Movement Report: ' . $exception->getMessage());
        }
    }

    private function getBulkData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName, $foodChargeReasonList)
    {
        return [
            'beginning_stock' => $this->getBulkBeginningStock($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName),
            'delivery' => $this->getBulkDeliveryData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName),
            'transfer' => $this->getBulkTransferData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName, $foodChargeReasonList),
            'conversion' => $this->getBulkConversionData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName),
            'stock_out' => $this->getBulkStockOutData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName),
            'actual_count' => $this->getBulkActualCount($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName),
        ];
    }

    private function getBulkBeginningStock($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName)
    {
        $subTractedDate = \Carbon\Carbon::parse($transactionDate)->subDay()->toDateString();
        $result = [];

        // Bulk query for stock inventory counts
        $query = DB::table('stock_inventory_count as sic')
            ->join('stock_inventory_items_count as siic', 'sic.id', '=', 'siic.stock_inventory_count_id')
            ->whereDate('sic.created_at', $subTractedDate)
            ->whereIn('sic.store_code', $storeCodes)
            ->whereIn('siic.item_code', $itemCodes)
            ->select('sic.store_code', 'sic.store_sub_unit_short_name', 'siic.item_code', 'siic.counted_quantity');

        if ($storeSubUnitShortName) {
            $query->where('sic.store_sub_unit_short_name', $storeSubUnitShortName);
        }

        $counts = $query->get();

        foreach ($counts as $count) {
            $key = "{$count->item_code}|{$count->store_code}|{$count->store_sub_unit_short_name}";
            $result[$key] = $count->counted_quantity;
        }

        return $result;
    }

    private function getBulkDeliveryData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName)
    {
        $query = StoreReceivingInventoryItemModel::select([
            'delivery_type',
            'reference_number',
            'is_received',
            'received_quantity',
            'item_code',
            'store_code',
            'store_sub_unit_short_name'
        ])
            ->whereIn('item_code', $itemCodes)
            ->whereIn('store_code', $storeCodes)
            ->whereDate('received_at', $transactionDate);

        if ($storeSubUnitShortName) {
            $query->where('store_sub_unit_short_name', $storeSubUnitShortName);
        }

        $deliveries = $query->get();
        $result = [];
        $referenceCodeArray = ['ST', 'SWS'];

        foreach ($deliveries as $delivery) {
            $key = "{$delivery->item_code}|{$delivery->store_code}|{$delivery->store_sub_unit_short_name}";

            if (!isset($result[$key])) {
                $result[$key] = ['1D' => 0, '2D' => 0, '3D' => 0, 'store_transfer_in' => 0];
            }

            $result[$key][$delivery->delivery_type] = ($result[$key][$delivery->delivery_type] ?? 0) + $delivery->received_quantity;

            $referenceCode = explode('-', $delivery->reference_number)[0] ?? null;
            if (in_array($referenceCode, $referenceCodeArray) && $delivery->is_received) {
                $result[$key]['store_transfer_in'] += $delivery->received_quantity;
            }
        }

        return $result;
    }

    private function getBulkTransferData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName, $foodChargeReasonList)
    {
        $query = StockTransferModel::with([
            'stockTransferItems' => function ($q) use ($itemCodes) {
                $q->whereIn('item_code', $itemCodes);
            }
        ])
            ->whereIn('store_code', $storeCodes)
            ->whereNotIn('status', [0, 1])
            ->whereDate('logistics_picked_up_at', $transactionDate);

        if ($storeSubUnitShortName) {
            $query->where('store_sub_unit_short_name', $storeSubUnitShortName);
        }

        $transfers = $query->get();
        $result = [];

        foreach ($transfers as $transfer) {
            foreach ($transfer->stockTransferItems as $item) {
                $key = "{$item->item_code}|{$transfer->store_code}|{$transfer->store_sub_unit_short_name}";

                if (!isset($result[$key])) {
                    $result[$key] = ['store_transfer_out' => 0, 'pullout' => 0, 'food_charge' => 0];
                }

                switch ($transfer->transfer_type) {
                    case 0:
                    case 2:
                        $result[$key]['store_transfer_out'] += $item->quantity;
                        break;
                    case 1:
                        if (in_array($transfer->remarks, $foodChargeReasonList)) {
                            $result[$key]['food_charge'] += $item->quantity;
                        } else {
                            $result[$key]['pullout'] += $item->quantity;
                        }
                        break;
                }
            }
        }

        return $result;
    }

    private function getBulkConversionData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName)
    {
        $query = StockConversionModel::with('stockConversionItems')
            ->whereIn('item_code', $itemCodes)
            ->whereIn('store_code', $storeCodes)
            ->whereDate('created_at', $transactionDate);

        if ($storeSubUnitShortName) {
            $query->where('store_sub_unit_short_name', $storeSubUnitShortName);
        }

        $conversions = $query->get();
        $result = [];

        foreach ($conversions as $conversion) {
            $key = "{$conversion->item_code}|{$conversion->store_code}|{$conversion->store_sub_unit_short_name}";

            if (!isset($result[$key])) {
                $result[$key] = ['convert_out' => 0, 'convert_in' => []];
            }

            $result[$key]['convert_out'] += $conversion->quantity;

            foreach ($conversion->stockConversionItems as $item) {
                $itemKey = "{$item->item_code}|{$conversion->store_code}|{$conversion->store_sub_unit_short_name}";
                $result[$key]['convert_in'][$itemKey] = [
                    'item_code' => $item->item_code,
                    'quantity' => $item->converted_quantity,
                ];
            }
        }

        return $result;
    }

    private function getBulkStockOutData($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName)
    {
        $query = StockOutModel::with([
            'stockOutItems' => function ($q) use ($itemCodes) {
                $q->whereIn('item_code', $itemCodes);
            }
        ])
            ->whereIn('store_code', $storeCodes)
            ->whereDate('created_at', $transactionDate);

        if ($storeSubUnitShortName) {
            $query->where('store_sub_unit_short_name', $storeSubUnitShortName);
        }

        $stockOuts = $query->get();
        $result = [];

        foreach ($stockOuts as $stockOut) {
            foreach ($stockOut->stockOutItems as $item) {
                $key = "{$item->item_code}|{$stockOut->store_code}|{$stockOut->store_sub_unit_short_name}";
                $result[$key] = ($result[$key] ?? 0) + $item->quantity;
            }
        }

        return $result;
    }

    private function getBulkActualCount($transactionDate, $itemCodes, $storeCodes, $storeSubUnitShortName)
    {
        $result = [];

        foreach ($itemCodes as $itemCode) {
            foreach ($storeCodes as $storeCode) {
                $actualCount = StockInventoryCountModel::onGetActualCountEOD($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName);
                $key = "{$itemCode}|{$storeCode}|{$storeSubUnitShortName}";
                $result[$key] = [
                    'counted_quantity' => $actualCount['counted_quantity'],
                    'remarks' => $actualCount['remarks']
                ];
            }
        }

        return $result;
    }

    private function applySorting($reportData, $isGroupByItemCategory, $isGroupByItemDescription)
    {
        if ($isGroupByItemCategory && $isGroupByItemDescription) {
            return collect($reportData)
                ->sortBy(fn ($item) => $item['item_category_name'] . '|' . $item['item_description'])
                ->toArray();
        } elseif ($isGroupByItemCategory) {
            return collect($reportData)->sortBy('item_category_name')->toArray();
        } elseif ($isGroupByItemDescription) {
            return collect($reportData)->sortBy('item_description')->toArray();
        }

        return $reportData;
    }
}
