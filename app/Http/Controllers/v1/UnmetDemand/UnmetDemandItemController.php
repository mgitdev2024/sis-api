<?php

namespace App\Http\Controllers\v1\UnmetDemand;

use App\Http\Controllers\Controller;
use App\Models\UnmetDemand\UnmetDemandItemModel;
use App\Models\UnmetDemand\UnmetDemandModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use DB;
use Exception;
use Illuminate\Http\Request;

class UnmetDemandItemController extends Controller
{
    use ResponseTrait;
    use CrudOperationsTrait;

    public function onDelete($id)
    {
        try {
            DB::beginTransaction();
            $unmetDemandItemModel = UnmetDemandItemModel::select('unmet_demand_id')->where('id', $id)->first();
            $unmetDemandId = $unmetDemandItemModel->unmet_demand_id;

            $unmetDemandModel = UnmetDemandItemModel::where('unmet_demand_id', $unmetDemandId)->count();
            if ($unmetDemandModel <= 1) {
                UnmetDemandModel::destroy($unmetDemandId);
                DB::commit();

                return $this->dataResponse('success', 200, __('msg.delete_success'));
            } else {
                return $this->deleteRecordById(UnmetDemandItemModel::class, $id, 'Unmet Demand Item');
            }
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, $exception->getMessage());
        }
    }


    public function onUpdate(Request $request, $unmet_demand_item_id)
    {
        $fields = $request->validate([
            'updated_data' => 'required', // [{"id":1,"quantity":10,"d":1},{"id":2,"quantity":5,"d":0}] d = delete flag
            'created_by_id' => 'required',
        ]);

        try {
            DB::beginTransaction();
            foreach (json_decode($fields['updated_data'], true) as $item) {
                $unmetDemandItemId = $item['id'];
                $unmetDemandItemModel = UnmetDemandItemModel::select('unmet_demand_id')->where('id', $unmetDemandItemId)->first();
                if (!UnmetDemandItemModel::where('id', $unmetDemandItemId)->exists()) {
                    continue;
                }
                $unmetDemandId = $unmetDemandItemModel->unmet_demand_id;

                if (isset($item['d']) && $item['d'] == 1) {
                    // Delete item
                    $unmetDemandModel = UnmetDemandItemModel::where('unmet_demand_id', $unmetDemandItemId)->count();
                    if ($unmetDemandModel <= 1) {
                        UnmetDemandModel::destroy($unmetDemandId);
                    } else {
                        UnmetDemandItemModel::where('id', $unmetDemandItemId)->delete();
                    }
                } elseif (isset($unmetDemandItemId)) {
                    // Update item
                    UnmetDemandItemModel::where('id', $unmetDemandItemId)->update([
                        'quantity' => $item['quantity'],
                        'updated_by_id' => $fields['created_by_id'],
                    ]);
                }
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, $exception->getMessage());
        }
    }

    public function onGet($unmet_demand_item_id)
    {
        try {
            $unmetDemandModel = UnmetDemandItemModel::with('unmetDemand')->where('unmet_demand_id', $unmet_demand_item_id)->get();
            return $this->dataResponse('success', 200, __('msg.record_found'), $unmetDemandModel);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 500, $exception->getMessage());
        }
    }
}
