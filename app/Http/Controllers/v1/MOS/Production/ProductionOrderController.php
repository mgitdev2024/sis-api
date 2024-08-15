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
                $productionOTA = new ProductionOTAModel();
                $productionOTB = new ProductionOTBModel();
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
            $arrString = '{"97":{"bid":138,"q":6,"sticker_status":1,"sticker_no":97,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-097","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"98":{"bid":138,"q":6,"sticker_status":1,"sticker_no":98,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-098","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"99":{"bid":138,"q":6,"sticker_status":1,"sticker_no":99,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-099","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"100":{"bid":138,"q":6,"sticker_status":1,"sticker_no":100,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-100","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"101":{"bid":138,"q":6,"sticker_status":1,"sticker_no":101,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-101","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"92":{"bid":138,"q":6,"sticker_status":1,"sticker_no":92,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-092","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"93":{"bid":138,"q":6,"sticker_status":1,"sticker_no":93,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-093","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"96":{"bid":138,"q":6,"sticker_status":1,"sticker_no":96,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-096","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"95":{"bid":138,"q":6,"sticker_status":1,"sticker_no":95,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-095","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"91":{"bid":138,"q":6,"sticker_status":1,"sticker_no":91,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-091","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"90":{"bid":138,"q":6,"sticker_status":1,"sticker_no":90,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-090","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"89":{"bid":138,"q":6,"sticker_status":1,"sticker_no":89,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-089","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"88":{"bid":138,"q":6,"sticker_status":1,"sticker_no":88,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-088","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"87":{"bid":138,"q":6,"sticker_status":1,"sticker_no":87,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-087","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"86":{"bid":138,"q":6,"sticker_status":1,"sticker_no":86,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-086","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"85":{"bid":138,"q":6,"sticker_status":1,"sticker_no":85,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-085","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"84":{"bid":138,"q":6,"sticker_status":1,"sticker_no":84,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-084","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"83":{"bid":138,"q":6,"sticker_status":1,"sticker_no":83,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-083","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"82":{"bid":138,"q":6,"sticker_status":1,"sticker_no":82,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-082","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"78":{"bid":138,"q":6,"sticker_status":1,"sticker_no":78,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-078","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"79":{"bid":138,"q":6,"sticker_status":1,"sticker_no":79,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-079","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"80":{"bid":138,"q":6,"sticker_status":1,"sticker_no":80,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-080","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"81":{"bid":138,"q":6,"sticker_status":1,"sticker_no":81,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-081","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"74":{"bid":138,"q":6,"sticker_status":1,"sticker_no":74,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-074","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"75":{"bid":138,"q":6,"sticker_status":1,"sticker_no":75,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-075","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"76":{"bid":138,"q":6,"sticker_status":1,"sticker_no":76,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-076","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"77":{"bid":138,"q":6,"sticker_status":1,"sticker_no":77,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-077","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"73":{"bid":138,"q":6,"sticker_status":1,"sticker_no":73,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-073","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"72":{"bid":138,"q":6,"sticker_status":1,"sticker_no":72,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-072","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"94":{"bid":138,"q":6,"sticker_status":1,"sticker_no":94,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-094","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"67":{"bid":138,"q":6,"sticker_status":1,"sticker_no":67,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-067","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"68":{"bid":138,"q":6,"sticker_status":1,"sticker_no":68,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-068","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"69":{"bid":138,"q":6,"sticker_status":1,"sticker_no":69,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-069","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"71":{"bid":138,"q":6,"sticker_status":1,"sticker_no":71,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-071","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"70":{"bid":138,"q":6,"sticker_status":1,"sticker_no":70,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-070","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"66":{"bid":138,"q":6,"sticker_status":1,"sticker_no":66,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-066","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"65":{"bid":138,"q":6,"sticker_status":1,"sticker_no":65,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-065","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"64":{"bid":138,"q":6,"sticker_status":1,"sticker_no":64,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-064","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"63":{"bid":138,"q":6,"sticker_status":1,"sticker_no":63,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-063","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"62":{"bid":138,"q":6,"sticker_status":1,"sticker_no":62,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-062","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"61":{"bid":138,"q":6,"sticker_status":1,"sticker_no":61,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-061","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"60":{"bid":138,"q":6,"sticker_status":1,"sticker_no":60,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-060","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"59":{"bid":138,"q":6,"sticker_status":1,"sticker_no":59,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-059","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"58":{"bid":138,"q":6,"sticker_status":1,"sticker_no":58,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-058","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"57":{"bid":138,"q":6,"sticker_status":1,"sticker_no":57,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-057","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"56":{"bid":138,"q":6,"sticker_status":1,"sticker_no":56,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-056","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"55":{"bid":138,"q":6,"sticker_status":1,"sticker_no":55,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-055","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"54":{"bid":138,"q":6,"sticker_status":1,"sticker_no":54,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-054","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"53":{"bid":138,"q":6,"sticker_status":1,"sticker_no":53,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-053","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"52":{"bid":138,"q":6,"sticker_status":1,"sticker_no":52,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-052","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"50":{"bid":138,"q":6,"sticker_status":1,"sticker_no":50,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-050","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"49":{"bid":138,"q":6,"sticker_status":1,"sticker_no":49,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-049","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"48":{"bid":138,"q":6,"sticker_status":1,"sticker_no":48,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-048","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"47":{"bid":138,"q":6,"sticker_status":1,"sticker_no":47,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-047","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"46":{"bid":138,"q":6,"sticker_status":1,"sticker_no":46,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-046","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"45":{"bid":138,"q":6,"sticker_status":1,"sticker_no":45,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-045","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"44":{"bid":138,"q":6,"sticker_status":1,"sticker_no":44,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-044","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"42":{"bid":138,"q":6,"sticker_status":1,"sticker_no":42,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-042","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"41":{"bid":138,"q":6,"sticker_status":1,"sticker_no":41,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-041","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"40":{"bid":138,"q":6,"sticker_status":1,"sticker_no":40,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-040","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"39":{"bid":138,"q":6,"sticker_status":1,"sticker_no":39,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-039","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"38":{"bid":138,"q":6,"sticker_status":1,"sticker_no":38,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-038","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"37":{"bid":138,"q":6,"sticker_status":1,"sticker_no":37,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-037","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"36":{"bid":138,"q":6,"sticker_status":1,"sticker_no":36,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-036","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"35":{"bid":138,"q":6,"sticker_status":1,"sticker_no":35,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-035","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"34":{"bid":138,"q":6,"sticker_status":1,"sticker_no":34,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-034","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"33":{"bid":138,"q":6,"sticker_status":1,"sticker_no":33,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-033","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"32":{"bid":138,"q":6,"sticker_status":1,"sticker_no":32,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-032","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"31":{"bid":138,"q":6,"sticker_status":1,"sticker_no":31,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-031","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"30":{"bid":138,"q":6,"sticker_status":1,"sticker_no":30,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-030","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"29":{"bid":138,"q":6,"sticker_status":1,"sticker_no":29,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-029","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}},"51":{"bid":138,"q":6,"sticker_status":1,"sticker_no":51,"status":3,"quality":"Fresh","parent_batch_code":"H16-EQ602-1D","sticker_multiplier":2,"batch_code":"H16-EQ602-1D-051","endorsed_by_qa":"0","warehouse":{"warehouse_receiving":{"reference_number":"8000046"}}}}';
            $data = json_decode($arrString, true);

            dd(count($data));
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
            dd($exception);
            return $this->dataResponse('error', 200, ProductionOrderModel::class . ' ' . __('msg.update_failed'));
        }
    }
}


// public function onDeleteById(Request $request,$id)
// {
//     return $this->deleteRecordById(ProductionOrderModel::class, $id, 'Production Order');
// }
