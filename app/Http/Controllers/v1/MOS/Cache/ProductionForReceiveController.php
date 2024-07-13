<?php

namespace App\Http\Controllers\v1\MOS\Cache;

use App\Http\Controllers\Controller;
use App\Models\MOS\Cache\ProductionForReceiveModel;
use App\Traits\MOS\MosCrudOperationsTrait;
use Illuminate\Http\Request;

class ProductionForReceiveController extends Controller
{
    use MosCrudOperationsTrait;
    public function onCacheForReceive(Request $request)
    {
        $rules = [
            'production_items' => 'required|json',
            'sub_location_id' => 'nullable|exists:wms_storage_sub_locations,id',
            'production_type' => 'required|in:0,1',  // 0 = otb, 1 = ota
            'created_by_id' => 'required'
        ];
        return $this->createRecord(ProductionForReceiveModel::class, $request, $rules, 'Production For Receive');
    }

    public function onGetCurrent($production_type, $created_by_id)
    {
        $productionForReceive = ProductionForReceiveModel::where([
            'created_by_id' => $created_by_id,
            'production_type' => $production_type,
        ])
            ->orderBy('id', 'DESC')
            ->first();

        if ($productionForReceive) {
            return $this->dataResponse('success', 200, __('msg.record_found'), $productionForReceive);
        }
        return $this->dataResponse('success', 200, __('msg.record_not_found'), $productionForReceive);
    }
    public function onDelete($production_type, $created_by_id)
    {
        try {
            $productionForReceive = ProductionForReceiveModel::where([
                'created_by_id' => $created_by_id,
                'production_type' => $production_type,
            ]);
            if ($productionForReceive->count() > 0) {
                $productionForReceive->delete();
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }

            return $this->dataResponse('success', 200, __('msg.record_not_found'));

        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        }
    }
}
