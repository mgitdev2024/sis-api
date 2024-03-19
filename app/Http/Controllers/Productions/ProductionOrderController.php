<?php

namespace App\Http\Controllers\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProductionOrder;
use App\Models\Productions\ProductionOTA;
use App\Models\Productions\ProductionOTB;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use DB;

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
    // public function onDeleteById($id)
    // {
    //     return $this->deleteRecordById(ProductionOrder::class, $id, 'Production Order');
    // }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionOrder::class, $id, 'Production Order');
    }

    public function onBulkUploadProductionOrder(Request $request)
    {
        $request->validate([
            'bulk_data' => 'required',
            'created_by_id' => 'required'
        ]);
        $bulkUploadData = $request->bulk_data;
        $createdById = $request->created_by_id;
        $referenceNumber = ProductionOrder::onGenerateProductionReferenceNumber();
        try {
            DB::beginTransaction();
            $productionOrder = new ProductionOrder();
            $productionOrder->reference_number = $referenceNumber;
            $productionOrder->production_date = date('Y-m-d', strtotime($bulkUploadData[0]['production_date']));
            $productionOrder->created_by_id = $request->created_by_id;
            $productionOrder->save();
            foreach ($bulkUploadData as $value) {
                $productionOTA = new ProductionOTA();
                $productionOTB = new ProductionOTB();
                if ($value['delivery_type'] != "" || $value['delivery_type'] != null) {
                    // Production OTB here
                    $productionOTB->production_order_id = $productionOrder->id;
                    $productionOTB->delivery_type = $value['delivery_type'];
                    $productionOTB->item_code = $value['item_code'];
                    $productionOTB->actual_quantity = $value['quantity'];
                    $productionOTB->buffer_level = floatval($value['buffer_level']) / 100;
                    $productionOTB->total_quantity = $value['total'];
                    $productionOTB->created_by_id = $createdById;
                    $productionOTB->save();
                } else {
                    // Production OTA here
                    $productionOTA->production_order_id = $productionOrder->id;
                    $productionOTA->item_code = $value['item_code'];
                    $productionOTA->actual_quantity = $value['quantity'];
                    $productionOTA->buffer_level = floatval($value['buffer_level']) / 100;
                    $productionOTA->total_quantity = $value['total'];
                    $productionOTA->created_by_id = $createdById;
                    $productionOTA->save();
                }
            }
            DB::commit();
            return $this->dataResponse('success', 201, __('msg.create_success'));
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }

    }
}
