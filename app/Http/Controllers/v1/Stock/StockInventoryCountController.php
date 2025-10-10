<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryCountModel;
use App\Models\Stock\StockInventoryCountTemplateModel;
use App\Models\Stock\StockInventoryItemCountModel;
use App\Models\Stock\StockInventoryModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use DB;
class StockInventoryCountController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'type' => 'required|in:1,2,3', // 1 = Hourly, 2 = EOD, 3 = Month-End
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'required',
            'selected_item_codes' => 'required', // ['CR 12','CR 6',...]
            'selection_template' => 'nullable', // JSON String
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];
            $selectedItemCodes = json_decode($fields['selected_item_codes'], true);
            $selectionTemplate = $fields['selection_template'] ?? null;

            $hasPending = StockInventoryCountModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName
            ])->whereIn('status', [0, 1])->exists();

            if ($hasPending) {
                return $this->dataResponse('error', 400, 'Still has pending stock count');
            }
            $referenceNumber = StockInventoryCountModel::onGenerateReferenceNumber();
            $type = $fields['type'];

            $stockCountDate = now();

            $response = \Http::withHeaders([
                'x-api-key' => config('apikeys.scm_api_key'),
            ])->get(config('apiurls.scm.url') . config('apiurls.scm.public_stock_count_lead_time_current_get'));

            if ($response->successful()) {
                $leadTime = $response->json()['success']['data'] ?? [];
                $leadTimeFrom = $leadTime['lead_time_from'] ?? null;
                $leadTimeTo = $leadTime['lead_time_to'] ?? null;
                $currentTime = now()->format('H:i:s');

                if (
                    Carbon::createFromTimeString($currentTime)
                        ->between(
                            Carbon::createFromTimeString($leadTimeFrom),
                            Carbon::createFromTimeString($leadTimeTo)
                        )
                ) {
                    $stockCountDate = now()->subDay(); // yesterday
                }
            }

            $stockInventoryCount = StockInventoryCountModel::create([
                'reference_number' => $referenceNumber,
                'type' => $type, // 1 = Hourly, 2 = EOD, 3 = Month-End
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'created_by_id' => $createdById,
                'updated_by_id' => $createdById,
                'status' => 0,
                'created_at' => $stockCountDate
            ]);
            $stockInventoryCount->save();

            $this->onCreateStockInventoryItemsCount($stockInventoryCount->id, $storeCode, $storeSubUnitShortName, $selectedItemCodes, $createdById);
            if ($selectionTemplate != null) {
                $this->onSaveStockCountTemplate($selectionTemplate, $storeCode, $storeSubUnitShortName, $createdById);
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onSaveStockCountTemplate($selectionTemplate, $storeCode, $storeSubUnitShortName, $createdById)
    {
        try {
            $existingTemplate = StockInventoryCountTemplateModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName
            ])->first();

            if ($existingTemplate) {
                $existingTemplate->selection_template = $selectionTemplate;
                $existingTemplate->updated_by_id = $createdById;
                $existingTemplate->save();
            } else {
                StockInventoryCountTemplateModel::create([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'selection_template' => $selectionTemplate,
                    'created_by_id' => $createdById,
                    'updated_by_id' => $createdById,
                    'status' => 1, // Active
                ]);
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCreateStockInventoryItemsCount($stockInventoryCountId, $storeCode, $storeSubUnitShortName, $selectedItemCodes, $createdById)
    {
        try {
            $existingItemCodes = [];
            $stockInventoryModel = StockInventoryModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
            ])
                ->whereIn('item_code', $selectedItemCodes)
                ->orderBy('item_code', 'DESC')
                ->get();

            $stockInventoryItemsCount = [];
            foreach ($stockInventoryModel as $item) {
                $existingItemCodes[] = $item->item_code;
                $stockInventoryItemsCount[] = [
                    'stock_inventory_count_id' => $stockInventoryCountId,
                    'item_code' => $item->item_code,
                    'item_description' => $item->item_description,
                    'item_category_name' => $item->item_category_name,
                    'system_quantity' => $item->stock_count,
                    'counted_quantity' => 0,
                    'discrepancy_quantity' => 0,
                    'created_at' => now(),
                    'created_by_id' => $createdById,
                    'updated_by_id' => $createdById,
                    'status' => 1, // For Receive
                ];
            }
            $toBeAddedItems = $this->onItemsDiff($existingItemCodes, $selectedItemCodes);
            if (count($toBeAddedItems) > 0) {
                $response = \Http::withHeaders([
                    'x-api-key' => config('apikeys.mgios_api_key'),
                ])->post(config('apiurls.mgios.url') . config('apiurls.mgios.public_item_masterdata_collection_get'), [
                            'item_code_collection' => json_encode($toBeAddedItems),
                        ]);

                $data = $response->json() ?? [];
                if (!empty($data)) {
                    foreach ($data as $item) {
                        $stockInventoryItemsCount[] = [
                            'stock_inventory_count_id' => $stockInventoryCountId,
                            'item_code' => $item['item_code'],
                            'item_description' => $item['long_name'],
                            'item_category_name' => $item['category_name'],
                            'system_quantity' => 0,
                            'counted_quantity' => 0,
                            'discrepancy_quantity' => 0,
                            'created_at' => now(),
                            'created_by_id' => $createdById,
                            'updated_by_id' => $createdById,
                            'status' => 1, // For Receive
                        ];
                    }
                }
            }
            StockInventoryItemCountModel::insert($stockInventoryItemsCount);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onItemsDiff($existingItemCodes, $selectedItemCodes)
    {
        return array_values(array_diff($selectedItemCodes, $existingItemCodes));
    }
    public function onGet($status, $store_code, $sub_unit = null)
    {
        try {
            $stockInventoryCountModel = StockInventoryCountModel::where('store_code', $store_code);
            if ($status == 0) {
                $stockInventoryCountModel->whereIn('status', [0, 1]);
            } else if ($status == 1) {
                $stockInventoryCountModel->where('status', 2);
            }
            if ($sub_unit) {
                $stockInventoryCountModel->where('store_sub_unit_short_name', $sub_unit);
            }
            $stockInventoryCountModel = $stockInventoryCountModel->orderBy('id', 'DESC')->get();
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockInventoryCountModel);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'), $exception->getMessage());
        }

    }
    public function onCancel(Request $request, $store_inventory_count_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $stockInventoryCountModel = StockInventoryCountModel::whereIn('status', [0, 1])->find($store_inventory_count_id);
            if (!$stockInventoryCountModel) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }
            $stockInventoryCountModel->status = 3; // Set status to Cancelled
            $stockInventoryCountModel->updated_by_id = $createdById;
            $stockInventoryCountModel->save();
            DB::commit();
            return $this->dataResponse('success', 200, 'Cancelled Successfully');
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Cancel Failed', $exception->getMessage());
        }
    }

    public function onGetItemByDepartment($store_code, $sub_unit)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where([
                'store_code' => $store_code,
                'store_sub_unit_short_name' => $sub_unit,
            ])
                ->orderBy('item_code', 'DESC')
                ->pluck('item_code');

            $response = \Http::get(config('apiurls.mgios.url') . config('apiurls.scm.get_item_by_department') . "$sub_unit/" . json_encode($stockInventoryModel));
            if ($response->successful()) {
                $data = $response->json();
                return $this->dataResponse('success', 200, 'record_found', $data);
            }
            return $this->dataResponse('error', 400, 'record_not_found');
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'record_not_found', $exception->getMessage());
        }
    }
}

