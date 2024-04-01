<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
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
            'created_by_id' => 'required|exists:credentials,id',
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
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOrderModel::class, $id, 'Production Order');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionOrderModel::class, $id, 'Production Order');
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
        }

        $orderFields = [
            "production_date" => "ASC",
        ];
        return $this->readCurrentRecord(ProductionOrderModel::class, $filter, $whereFields, null, $orderFields, 'Production Order');
    }
    public function onBulkUploadProductionOrder(Request $request)
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
}


// public function onDeleteById($id)
// {
//     return $this->deleteRecordById(ProductionOrderModel::class, $id, 'Production Order');
// }
