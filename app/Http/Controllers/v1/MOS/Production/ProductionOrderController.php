<?php

namespace App\Http\Controllers\v1\MOS\Production;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\MOS\Production\ProductionOrderModel;
use App\Models\MOS\Production\ProductionOTAModel;
use App\Models\MOS\Production\ProductionOTBModel;
use Illuminate\Http\Request;
use App\Traits\MOS\MosCrudOperationsTrait;
use DB;
use Illuminate\Validation\Rule;

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
        } catch (\Exception $exception) {
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
            "production_date" => "ASC",
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
            foreach ($bulkUploadData as $value) {
                $productionOTA = new ProductionOTAModel();
                $productionOTB = new ProductionOTBModel();
                $itemMasterdata = ItemMasterdataModel::where('item_code', $value['item_code'])
                    ->first();
                if (!$itemMasterdata) {
                    continue;
                }
                $itemCategory = $itemMasterdata
                    ->itemCategory
                    ->name;
                $requestedQuantity = intval($value['quantity']);
                $bufferLevel = $value['buffer_quantity'] ? round((intval($value['buffer_quantity']) / $requestedQuantity) * 100, 2) : 0;
                $bufferQuantity = intval($value['buffer_quantity']);
                if (strcasecmp($itemCategory, 'Breads') === 0) {
                    $existingOTB = ProductionOTBModel::where('production_order_id', $productionOrder->id)
                        ->where('item_code', $value['item_code'])
                        ->where('delivery_type', $value['delivery_type'])
                        ->exists();
                    if ($existingOTB) {
                        $duplicates[] = $value['item_code'];
                        continue;
                    }

                    $productionOTB->production_order_id = $productionOrder->id;
                    $productionOTB->delivery_type = $value['delivery_type'];
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
            } else {
                DB::commit();
            }

            return $this->dataResponse('success', 200, $message, $response);

        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }

    }

    public function onGetBatches(Request $request, $id, $order_type)
    {
        $productionOrder = ProductionOrderModel::find($id);
        if ($productionOrder) {
            $otbIds = $productionOrder->productionOtb->pluck('id')->toArray();
            $otaIds = $productionOrder->productionOta->pluck('id')->toArray();
            $productionBatches = ProductionBatchModel::orderBy('id', 'ASC');

            if (strcasecmp($order_type, 'otb') === 0) {
                $productionBatches->whereIn('production_otb_id', $otbIds);
            } else {
                $productionBatches->whereIn('production_ota_id', $otaIds);
            }

            $productionBatches = $productionBatches->get();

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
}


// public function onDeleteById(Request $request,$id)
// {
//     return $this->deleteRecordById(ProductionOrderModel::class, $id, 'Production Order');
// }
