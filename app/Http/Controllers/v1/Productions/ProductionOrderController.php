<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Items\ItemMasterdata;
use App\Models\Productions\ProductionOrder;
use App\Models\Productions\ProductionOTA;
use App\Models\Productions\ProductionOTB;
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
            'created_by_id' => 'required|exists:credentials,id',
            'reference_number' => 'required|string|unique:production_orders,reference_number,' . $orderId,
            'production_date' => 'required|date_format:Y-m-d',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionOrder::class, $request, $this->getRules(), 'Production Order');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ProductionOrder::class, $request, $this->getRules($id), 'Production Order', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOrder::class, $request, $searchableFields, 'Production Order');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOrder::class, $id, 'Production Order');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionOrder::class, $id, 'Production Order');
    }
    public function onGetCurrent($id = null)
    {
        $whereFields = [
            'status' => $id != null ? 0 : 1
        ];
        $id != null ? $whereFields['id'] = $id : "";
        return $this->readCurrentRecord(ProductionOrder::class, $id, $whereFields, 'Production Order');
    }
    public function onBulkUploadProductionOrder(Request $request)
    {
        $request->validate([
            'bulk_data' => 'required',
            'created_by_id' => 'required'
        ]);
        // $bulkUploadData = json_decode($request->bulk_data, true);
        $bulkUploadData = $request->bulk_data;
        $createdById = $request->created_by_id;
        $referenceNumber = ProductionOrder::onGenerateProductionReferenceNumber();
        $existingProductionOrderOpen = ProductionOrder::where('status', 1)->get();
        $duplicates = [];
        try {
            if (!count($existingProductionOrderOpen) > 0) {
                DB::beginTransaction();
                $productionOrder = new ProductionOrder();
                $productionOrder->reference_number = $referenceNumber;
                $productionOrder->production_date = date('Y-m-d', strtotime($bulkUploadData[0]['production_date']));
                $productionOrder->created_by_id = $request->created_by_id;
                $productionOrder->save();
                foreach ($bulkUploadData as $value) {
                    $productionOTA = new ProductionOTA();
                    $productionOTB = new ProductionOTB();
                    $itemClassification = ItemMasterdata::where('item_code', $value['item_code'])
                        ->first()
                        ->itemClassification
                        ->name;
                    if (strcasecmp($itemClassification, 'Breads') === 0) {
                        $existingOTB = ProductionOTB::where('production_order_id', $productionOrder->id)
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
                        $productionOTB->buffer_level = floatval($value['buffer_level']) / 100;
                        $productionOTB->plotted_quantity = $value['total'];
                        $productionOTB->created_by_id = $createdById;
                        $productionOTB->save();
                    } else {
                        $existingOTA = ProductionOTA::where('production_order_id', $productionOrder->id)
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
                        $productionOTA->created_by_id = $createdById;
                        $productionOTA->save();
                    }
                }
                $response = [
                    "is_duplicate" => false,
                    "is_previous_production_order_open" => false,
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
            } else {
                $response = [
                    "is_duplicate" => false,
                    "is_previous_production_order_open" => true,
                ];
                return $this->dataResponse('success', 200, "Bulk upload failed", $response);
            }

        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }

    }
}


// public function onDeleteById($id)
// {
//     return $this->deleteRecordById(ProductionOrder::class, $id, 'Production Order');
// }
