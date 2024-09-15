<?php

namespace App\Http\Controllers\v1\MOS\Production;

use App\Http\Controllers\Controller;
use App\Models\History\PrintHistoryModel;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\MOS\Production\ProductionOrderModel;
use App\Models\MOS\Production\ProductionOTAModel;
use App\Models\MOS\Production\ProductionOTBModel;
use Illuminate\Http\Request;
use App\Traits\MOS\MosCrudOperationsTrait;
use DB;
use Illuminate\Validation\Rule;
use Exception;

class ProductionOrderController extends Controller
{
    use MosCrudOperationsTrait;

    public static function getRules($orderId = "")
    {
        return [
            'created_by_id' => 'required',
            'reference_number' => 'required|string|unique:mos_production_orders,reference_number,' . $orderId,
            'production_date' => 'required|date_format:Y-m-d',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionOrderModel::class, $request, $this->getRules(), 'Production Order');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ProductionOrderModel::class, $request, $this->getRules($id), 'Production Order', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOrderModel::class, $request, $searchableFields, 'Production Order');
    }
    public function onGetAll()
    {
        return $this->readRecord(ProductionOrderModel::class, 'Production Order');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOrderModel::class, $id, 'Production Order');
    }
    public function onChangeStatus(Request $request, $id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required'
        ]);
        try {
            $productionOrder = ProductionOrderModel::find($id);
            if ($productionOrder) {
                DB::beginTransaction();
                $response = $productionOrder->toArray();
                $response['status'] = !$response['status'];
                $productionOrder->update($response);

                $otbIds = $productionOrder->productionOtb->pluck('id')->toArray();
                $otaIds = $productionOrder->productionOta->pluck('id')->toArray();
                $productionBatches = ProductionBatchModel::whereIn('production_otb_id', $otbIds)
                    ->orWhereIn('production_ota_id', $otaIds)
                    ->get();

                $batchStatus = $response['status'] == 1 ? 2 : 0;
                foreach ($productionBatches as $batch) {
                    if ($batch->status !== 1) {
                        $batch->status = $batchStatus;
                        $batch->update();
                    }
                }
                $this->createProductionLog(ProductionOrderModel::class, $productionOrder->id, $productionOrder->getAttributes(), $fields['created_by_id'], 1);
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $response);
            }
            return $this->dataResponse('error', 200, ProductionOrderModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetCurrent($filter = null)
    {
        $whereFields = [];
        $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
        if ($whereObject && $whereObject->format('Y-m-d') === $filter) {
            $whereFields['production_date'] = $filter;
        } elseif ($filter) {
            $filter != null ? $whereFields['id'] = $filter : "";
        } else {
            $today = new \DateTime('today');
            $tomorrow = new \DateTime('tomorrow');
            $whereFields['production_date'] = [$today->format('Y-m-d'), $tomorrow->format('Y-m-d')];
            $whereFields['status'] = [0];
        }

        $orderFields = [
            "created_at" => "DESC",
        ];
        return $this->readCurrentRecord(ProductionOrderModel::class, $filter, $whereFields, null, $orderFields, 'Production Order', true);
    }
    public function onBulk(Request $request)
    {
        $request->validate([
            'bulk_data' => 'required',
            'created_by_id' => 'required'
        ]);
        $bulkUploadData = json_decode($request->bulk_data, true);
        $createdById = $request->created_by_id;
        $referenceNumber = ProductionOrderModel::onGenerateProductionReferenceNumber();
        $duplicates = [];
        try {
            DB::beginTransaction();
            $productionDate = date('Y-m-d', strtotime($bulkUploadData[0]['production_date']));
            $productionOrder = new ProductionOrderModel();
            $productionOrder->reference_number = $referenceNumber;
            $productionOrder->production_date = $productionDate;
            $productionOrder->created_by_id = $request->created_by_id;
            $productionOrder->save();
            $this->createProductionLog(ProductionOrderModel::class, $productionOrder->id, $productionOrder->getAttributes(), $createdById, 0);
            $itemMasterDataCounter = 0;
            foreach ($bulkUploadData as $value) {
                $itemMasterdata = ItemMasterdataModel::where('item_code', $value['item_code'])
                    ->first();
                if (!$itemMasterdata) {
                    continue;
                }

                $itemMasterDataCounter++;
                $itemCategory = $itemMasterdata
                    ->itemCategory
                    ->name;
                $requestedQuantity = intval($value['quantity']);
                $bufferLevel = $value['buffer_quantity'] ? round((intval($value['buffer_quantity']) / $requestedQuantity) * 100, 2) : 0;
                $bufferQuantity = intval($value['buffer_quantity']);
                if (strcasecmp($itemCategory, 'Breads') === 0) {
                    $productionOTB = new ProductionOTBModel();

                    $existingOTB = ProductionOTBModel::where('production_order_id', $productionOrder->id)
                        ->where('item_code', $value['item_code'])
                        ->where('delivery_type', $value['delivery_type'])
                        ->exists();
                    if ($existingOTB) {
                        $duplicates[] = $value['item_code'];
                        continue;
                    }

                    $productionOTB->production_order_id = $productionOrder->id;
                    $productionOTB->delivery_type = $value['delivery_type'] != "" ? $value['delivery_type'] : null;
                    $productionOTB->item_code = $value['item_code'];
                    $productionOTB->requested_quantity = $requestedQuantity;
                    $productionOTB->buffer_level = $bufferLevel;
                    $productionOTB->buffer_quantity = $bufferQuantity;
                    $productionOTB->plotted_quantity = $requestedQuantity + $bufferQuantity;

                    if ($itemMasterdata->chilled_shelf_life) {
                        $productionOTB->expected_chilled_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->chilled_shelf_life . ' days'));
                    }
                    if ($itemMasterdata->frozen_shelf_life) {
                        $productionOTB->expected_frozen_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->frozen_shelf_life . ' days'));
                    }
                    if ($itemMasterdata->ambient_shelf_life) {
                        $productionOTB->expected_ambient_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->ambient_shelf_life . ' days'));
                    }
                    $productionOTB->created_by_id = $createdById;
                    $productionOTB->save();

                    $this->createProductionLog(ProductionOTBModel::class, $productionOTB->id, $productionOTB->getAttributes(), $createdById, 0);
                } else {
                    $productionOTA = new ProductionOTAModel();

                    $existingOTA = ProductionOTAModel::where('production_order_id', $productionOrder->id)
                        ->where('item_code', $value['item_code'])
                        ->exists();

                    if ($existingOTA) {
                        $duplicates[] = $value['item_code'];
                        continue;
                    }
                    $productionOTA->production_order_id = $productionOrder->id;
                    $productionOTA->item_code = $value['item_code'];
                    $productionOTA->requested_quantity = $requestedQuantity;
                    $productionOTA->buffer_level = $bufferLevel;
                    $productionOTA->buffer_quantity = $bufferQuantity;
                    $productionOTA->plotted_quantity = $requestedQuantity + $bufferQuantity;
                    if ($itemMasterdata->chilled_shelf_life) {
                        $productionOTA->expected_chilled_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->chilled_shelf_life . ' days'));
                    }
                    if ($itemMasterdata->frozen_shelf_life) {
                        $productionOTA->expected_frozen_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->frozen_shelf_life . ' days'));
                    }
                    if ($itemMasterdata->ambient_shelf_life) {
                        $productionOTA->expected_ambient_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->ambient_shelf_life . ' days'));
                    }

                    $productionOTA->created_by_id = $createdById;
                    $productionOTA->save();
                    $this->createProductionLog(ProductionOTAModel::class, $productionOTA->id, $productionOTA->getAttributes(), $createdById, 0);
                }
            }
            $response = [
                "is_duplicate" => false,
            ];
            $message = "Bulk upload success";
            if (count($duplicates) > 0) {
                $message = "Bulk upload cancelled: Duplicate entries were uploaded";
                $response["is_duplicate"] = true;
                $response['duplicated_entries'] = $duplicates;
            }

            if ($itemMasterDataCounter > 0) {
                DB::commit();
                return $this->dataResponse('success', 200, $message, $response);
            } else {
                return $this->dataResponse('error', 200, 'No Item Masterdata found.');
            }


        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }

    }

    public function onGetBatches(Request $request, $production_order_id, $order_type)
    {
        $productionOrder = ProductionOrderModel::find($production_order_id);
        if ($productionOrder) {

            $productionBatchAdd = ProductionBatchModel::with(['productionOtb', 'productionOta']);
            $inclusionExclusionItemCode = ItemMasterdataModel::getViewableOtb(true);

            if (strcasecmp($order_type, 'otb') === 0) {
                $productionBatchAdd->where(function ($query) use ($inclusionExclusionItemCode) {
                    $query->whereHas('productionOta', function ($query) use ($inclusionExclusionItemCode) {
                        $query->whereIn('item_code', $inclusionExclusionItemCode);
                    })
                        ->orWhereNotNull('production_otb_id');
                });
            } else {
                $productionBatchAdd->where(function ($query) use ($inclusionExclusionItemCode) {
                    $query->whereHas('productionOta', function ($query) use ($inclusionExclusionItemCode) {
                        $query->whereNotIn('item_code', $inclusionExclusionItemCode);
                    })
                        ->whereNotNull('production_ota_id');
                });
            }

            $productionBatches = $productionBatchAdd->get();

            foreach ($productionBatches as $value) {
                $value['batch_quantity'] = count(json_decode($value->productionItems->produced_items, true));
            }

            $response = [
                'batches' => $productionBatches,
            ];
            return $this->dataResponse('success', 200, __('msg.record_found'), $response);
        }
        return $this->dataResponse('error', 200, ProductionOrderModel::class . ' ' . __('msg.record_not_found'));
    }

    public function onAlignProductionCount(Request $request, $production_order_id)
    {
        try {

            DB::beginTransaction();
            $productionOrder = ProductionOrderModel::find($production_order_id);
            if ($productionOrder) {
                $productionBatches = ProductionBatchModel::where('production_order_id', $production_order_id)
                    ->get();
                $productionItems = [];
                $productionArr = [];
                foreach ($productionBatches as $batch) {
                    $productionItems = json_decode($batch->productionItems->produced_items, true);

                    $producedItemCount = 0;
                    $receivedItemCount = 0;
                    foreach ($productionItems as $itemValue) {
                        if ($itemValue['sticker_status'] == 1) {
                            $producedItemCount++;
                        }
                        if ($itemValue['status'] == 3) {
                            $receivedItemCount++;
                        }
                    }
                    $productionToBakeAssemble = $batch->productionOta ?? $batch->productionOtb;
                    $productionType = $batch->productionOta ? 1 : 0;
                    if (isset($productionArr[$productionType . '-' . $productionToBakeAssemble->id])) {
                        $productionArr[$productionType . '-' . $productionToBakeAssemble->id]['producedItemCount'] += $producedItemCount;
                        $productionArr[$productionType . '-' . $productionToBakeAssemble->id]['receivedItemCount'] += $receivedItemCount;
                    } else {
                        $productionArr[$productionType . '-' . $productionToBakeAssemble->id] = [
                            'productionToBakeAssemble' => $productionToBakeAssemble,
                            'item_code' => $productionToBakeAssemble->item_code,
                            'producedItemCount' => $producedItemCount,
                            'receivedItemCount' => $receivedItemCount,
                        ];
                    }
                }
                foreach ($productionArr as $value) {
                    $value['productionToBakeAssemble']->produced_items_count = $value['producedItemCount'];
                    $value['productionToBakeAssemble']->received_items_count = $value['receivedItemCount'];
                    $value['productionToBakeAssemble']->update();
                }
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'));
            } else {
                return $this->dataResponse('error', 200, 'Production Order  ' . __('msg.record_not_found'));
            }
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 200, ProductionOrderModel::class . ' ' . __('msg.update_failed'));
        }
    }

    public function onAdditionalOtaOtb(Request $request, $production_order_id)
    {
        try {

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}


// public function onDeleteById(Request $request,$id)
// {
//     return $this->deleteRecordById(ProductionOrderModel::class, $id, 'Production Order');
// }
