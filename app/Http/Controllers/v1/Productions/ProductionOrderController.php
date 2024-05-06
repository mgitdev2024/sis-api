<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\History\ProductionHistoricalLogController;
use App\Models\Productions\ProductionBatchModel;
use App\Models\Settings\Items\ItemMasterdataModel;
use App\Models\Productions\ProductionOrderModel;
use App\Models\Productions\ProductionOTAModel;
use App\Models\Productions\ProductionOTBModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use DB;
use Illuminate\Validation\Rule;

class ProductionOrderController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($orderId = "")
    {
        return [
            'created_by_id' => 'required',
            'reference_number' => 'required|string|unique:production_orders,reference_number,' . $orderId,
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
    public function onGetAll(Request $request)
    {
        return $this->readRecord(ProductionOrderModel::class, $request,'Production Order');
    }
    public function onGetById(Request $request,$id)
    {
        return $this->readRecordById(ProductionOrderModel::class, $id,$request, 'Production Order');
    }
    public function onChangeStatus(Request $request,$id)
    {

        $token = $request->bearerToken();
        $this->authenticateToken($token);
        try {
            $productionOrder = ProductionOrderModel::find($id);
            if ($productionOrder) {
                DB::beginTransaction();
                $response = $productionOrder->toArray();
                $response['status'] = 1;
                $productionOrder->update($response);

                $otbIds = $productionOrder->productionOtb->pluck('id')->toArray();
                $otaIds = $productionOrder->productionOta->pluck('id')->toArray();
                $productionBatches = ProductionBatchModel::whereIn('production_otb_id', $otbIds)
                    ->orWhereIn('production_ota_id', $otaIds)
                    ->get();

                foreach ($productionBatches as $batch) {
                    if ($batch->status !== 1) {
                        $batch->status = 2;
                        $batch->update();
                    }
                }

                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $response);
            }
            return $this->dataResponse('error', 200, ProductionOrderModel::class . ' ' . __('msg.record_not_found'));
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetCurrent(Request $request,$filter = null)
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
        }

        $orderFields = [
            "production_date" => "ASC",
        ];
        return $this->readCurrentRecord(ProductionOrderModel::class, $filter, $whereFields, null, $orderFields, $request, 'Production Order');
    }
    public function onBulkUploadProductionOrder(Request $request)
    {

        $token = $request->bearerToken();
        $this->authenticateToken($token);
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
            foreach ($bulkUploadData as $value) {
                $productionOTA = new ProductionOTAModel();
                $productionOTB = new ProductionOTBModel();
                $itemMasterdata = ItemMasterdataModel::where('item_code', $value['item_code'])
                    ->first();
                $itemClassification = $itemMasterdata
                    ->itemClassification
                    ->name;
                if (strcasecmp($itemClassification, 'Breads') === 0) {
                    $existingOTB = ProductionOTBModel::where('production_order_id', $productionOrder->id)
                        ->where('item_code', $value['item_code'])
                        ->exists();
                    if ($existingOTB) {
                        $duplicates[] = $value['item_code'];
                        continue;
                    }
                    $productionOTB->production_order_id = $productionOrder->id;
                    $productionOTB->delivery_type = $value['delivery_type'];
                    $productionOTB->item_code = $value['item_code'];
                    $productionOTB->requested_quantity = $value['quantity'];
                    $productionOTB->buffer_level = floatval(str_replace('%', '', $value['buffer_level'])) / 100;
                    $productionOTB->plotted_quantity = $value['total'];
                    if ($itemMasterdata->chilled_shelf_life) {
                        $productionOTB->expected_chilled_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->chilled_shelf_life . ' days'));
                    }
                    if ($itemMasterdata->frozen_shelf_life) {
                        $productionOTB->expected_frozen_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->frozen_shelf_life . ' days'));
                    }

                    $productionOTB->created_by_id = $createdById;
                    $productionOTB->save();
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
                    $productionOTA->requested_quantity = $value['quantity'];
                    $productionOTA->buffer_level = floatval($value['buffer_level']) / 100;
                    $productionOTA->plotted_quantity = $value['total'];
                    if ($itemMasterdata->chilled_shelf_life) {
                        $productionOTA->expected_chilled_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->chilled_shelf_life . ' days'));
                    }
                    if ($itemMasterdata->frozen_shelf_life) {
                        $productionOTA->expected_frozen_exp_date = date('Y-m-d', strtotime($productionDate . ' + ' . $itemMasterdata->frozen_shelf_life . ' days'));
                    }

                    $productionOTA->created_by_id = $createdById;
                    $productionOTA->save();
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

    public function onGetBatches(Request $request ,$id, $order_type)
    {
        $token = $request->bearerToken();
        $this->authenticateToken($token);
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
