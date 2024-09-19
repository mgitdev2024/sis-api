<?php

namespace App\Http\Controllers\v1\History;

use App\Http\Controllers\Controller;
use App\Models\History\PrintHistoryModel;
use App\Models\MOS\Production\ProductionOrderModel;
use App\Traits\Admin\CredentialsTrait;
use Illuminate\Http\Request;
use App\Traits\MOS\MosCrudOperationsTrait;
use Exception;
use DB;
use Storage;

class PrintHistoryController extends Controller
{
    use MosCrudOperationsTrait, CredentialsTrait;

    public function getRules()
    {
        return [
            'production_batch_id' => 'required|integer',
            'produced_items' => 'required|string',
            'reason' => 'nullable|string',
            'attachment' => 'nullable',
            'is_reprint' => 'required|boolean',
            'item_disposition_id' => 'nullable|integer',
            'created_by_id' => 'required'
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->getRules());

        try {
            DB::beginTransaction();
            $record = new PrintHistoryModel();
            $record->fill($fields);


            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('public/attachments/print-history');
                $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
                $record->attachment = $filepath;
            }

            $record->save();
            DB::commit();
            return $this->dataResponse('success', 201, 'Print History ' . __('msg.create_success'), $record);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }
    public function onGetAll()
    {
        return $this->readRecord(PrintHistoryModel::class, 'Print History');
    }

    public function onGetCurrent($id)
    {
        $whereFields = [];
        if ($id != null) {
            $whereFields = [
                'production_batch_id' => $id
            ];
        }
        return $this->readCurrentRecord(PrintHistoryModel::class, $id, $whereFields, null, null, 'Print History');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(PrintHistoryModel::class, $id, 'Print History');
    }



    public function onGetPrintedDetails($filter = null)
    {
        try {
            $whereFields = [];
            $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
            if ($whereObject && $whereObject->format('Y-m-d') === $filter) {
                $whereFields['production_date'] = $filter;
            } elseif ($filter) {
                $filter != null ? $whereFields['id'] = $filter : "";
            } else {
                $today = new \DateTime('today');
                $whereFields['production_date'] = $today->format('Y-m-d');
                $whereFields['status'] = 0;
            }

            $printHistoryModel = PrintHistoryModel::with('productionBatch.productionOrder')
                ->whereHas('productionBatch.productionOrder', function ($query) use ($whereFields) {
                    foreach ($whereFields as $key => $value) {
                        $query->where($key, $value);
                    }
                })
                ->where([
                    'is_reprint' => 0
                ])
                ->get();
            $response = [];
            // dd($whereFields);
            foreach ($printHistoryModel as $printHistory) {
                $productionBatch = $printHistory->productionBatch;
                $productionToBakeAssemble = $productionBatch->productionOta ?? $productionBatch->productionOtb;
                $itemMasterdata = $productionToBakeAssemble->itemMasterdata;
                $deliveryScheme = $productionToBakeAssemble->delivery_type ?? 'N/A';

                $itemCode = $itemMasterdata->item_code;
                $productionOrder = $productionBatch->productionOrder;
                $itemCategory = $itemMasterdata->item_category_label;

                $chilledShelfLife = $itemMasterdata->chilled_shelf_life;
                $frozenShelfLife = $itemMasterdata->frozen_shelf_life;
                $ambienShelfLife = $itemMasterdata->ambient_shelf_life;
                $chilledExpiration = $productionBatch->chilled_exp_date;
                $frozenExpiration = $productionBatch->frozen_exp_date;
                $ambienExpiration = $productionBatch->ambient_shelf_life;

                $data = [
                    'PD' => $productionOrder->production_date,
                    'BC' => $productionBatch->batch_code,
                ];

                if ($chilledShelfLife) {
                    $data['CSL'] = $chilledShelfLife;
                    $data['CED'] = $chilledExpiration;
                }
                if ($frozenShelfLife) {
                    $data['FSL'] = $frozenShelfLife;
                    $data['FED'] = $frozenExpiration;
                }
                if (!$chilledShelfLife && !$frozenShelfLife) {
                    $data['ASL'] = $ambienShelfLife;
                    $data['AED'] = $ambienExpiration;
                }

                $response[] = [
                    'id' => $printHistory->id,
                    'item_category' => $itemCategory,
                    'item_code' => $itemCode,
                    'item_details' => $data,
                    'delivery_scheme' => $deliveryScheme,
                    'quantity' => count(json_decode($printHistory->produced_items, true)),
                    'printed_by' => $this->onGetName($printHistory->created_by_id),
                    'printed_at' => date('Y-m-d (h:i:A)', strtotime($printHistory->created_at))
                ];
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $response);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 200, ProductionOrderModel::class . ' ' . __('msg.record_not_found'));
        }
    }
}
