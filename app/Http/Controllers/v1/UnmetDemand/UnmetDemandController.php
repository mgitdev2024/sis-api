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

class UnmetDemandController extends Controller
{
    use ResponseTrait;
    use CrudOperationsTrait;
    public function onDelete($id)
    {
        return $this->deleteRecordById(UnmetDemandModel::class, $id, 'Unmet Demand');
    }

    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'store_code' => 'required|string',
            'store_sub_unit_short_name' => 'required|string',
            'unmet_items' => 'required',
            'created_by_id' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];
            $createdById = $fields['created_by_id'];
            $unmetItems = json_decode($fields['unmet_items'], true); // [{"ic":"CR 12","itd":"Cheeseroll box of 12","itc":"Breads","q":12},{"ic":"CR 12","itd":"Cheeseroll box of 12","itc":"Breads","q":10}]
            $referenceNumber = UnmetDemandModel::onGenerateReferenceNumber();

            $unmetDemand = UnmetDemandModel::create([
                'reference_code' => $referenceNumber,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'status' => 1,
                'created_by_id' => $createdById,
            ]);
            foreach ($unmetItems as $item) {
                UnmetDemandItemModel::insert([
                    'unmet_demand_id' => $unmetDemand->id,
                    'item_code' => $item['ic'],
                    'item_description' => $item['itd'],
                    'item_category_name' => $item['itc'],
                    'quantity' => $item['q'],
                    'status' => 1,
                    'created_by_id' => $createdById,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, $exception->getMessage());
        }
    }
}
