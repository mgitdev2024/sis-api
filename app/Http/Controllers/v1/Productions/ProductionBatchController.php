<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionBatchModel;
use App\Models\Productions\ProductionOTBModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
use DB;

class ProductionBatchController extends Controller
{
    use ResponseTrait;
    public static function onGetRules()
    {
        return [
            'production_batch_code' => 'nullable|string|exists:production_batch,batch_code',
            'production_batch_number' => 'required_with:production_batch_code',
            'production_otb_id' => 'required|exists:production_otb,id',
            'batch_type' => 'required|integer|in:0,1',
            'quantity' => 'required',
            'expiration_date' => 'nullable|date',
            'created_by_id' => 'required|exists:credentials,id',
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->onGetRules());
        try {
            DB::beginTransaction();
            dd(json_decode($fields['quantity'], true));
            if (isset ($fields['production_batch_code'])) {
                $addToExistingBatch = $this->onAddToExistingBatch($fields);
            } else {
                $newBatch = $this->onInitialBatch($fields);
            }

            DB::commit();
            return $this->dataResponse('success', 201, 'Production Batch' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
            // return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }

    public function onAddToExistingBatch($fields)
    {
        $batchCode = $fields['production_batch_code'];
        $batchNumber = $fields['production_batch_number'];

        $productionBatch = new ProductionBatchModel();
        $productionBatch->fill($fields);
        $productionBatch->batch_code = $batchCode;
        $productionBatch->batch_number = $batchNumber;
        $productionBatch->save();

        return $productionBatch;
    }

    public function onInitialBatch($fields)
    {
        $productionOtb = ProductionOTBModel::find($fields['production_otb_id']);
        $itemCode = $productionOtb->item_code;
        $deliveryType = $productionOtb->delivery_type;
        $batchNumber = count(ProductionBatchModel::where('production_otb_id', $productionOtb->id)->get()) + 1;
        $batchCode = ProductionBatchModel::generateBatchCode($itemCode, $deliveryType, $batchNumber);
        $productionBatch = new ProductionBatchModel();
        $productionBatch->fill($fields);
        $productionBatch->batch_code = $batchCode;
        $productionBatch->batch_number = $batchNumber;
        $productionBatch->save();

        return $productionBatch;
    }

    public function onGenerateProducedItems()
    {

    }
}
