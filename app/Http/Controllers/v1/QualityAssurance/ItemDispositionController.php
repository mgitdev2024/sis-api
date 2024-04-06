<?php

namespace App\Http\Controllers\v1\QualityAssurance;

use App\Http\Controllers\Controller;
use App\Models\QualityAssurance\ItemDispositionModel;
use Illuminate\Http\Request;
use Exception;
use DB;
use App\Traits\CrudOperationsTrait;

class ItemDispositionController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'production_order_id' => 'required|exists:production_orders,id',
            'item_code' => 'required|string',
            'production_date' => 'required|date_format:Y-m-d',
        ];
    }
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'plotted_quantity' => 'required|integer',
            'actual_quantity' => 'nullable|integer',
        ];
        return $this->updateRecordById(ItemDispositionModel::class, $request, $rules, 'Item Disposition', $id);
    }
    public function onGetCurrent($type = null, $status)
    {
        $typeArray = [0, 1];
        $whereFields = [];
        if (in_array($type, $typeArray)) {
            $whereFields = [
                'status' => $status,
                'type' => $type
            ];
        } else {
            $whereFields = [
                'status' => $status
            ];
        }
        return $this->readCurrentRecord(ItemDispositionModel::class, $type, $whereFields, null, null, 'Item Disposition');
    }
    public function onGetAll()
    {
        return $this->readRecord(ItemDispositionModel::class, 'Item Disposition');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemDispositionModel::class, $id, 'Item Disposition');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemDispositionModel::class, $id, 'Item Disposition');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ItemDispositionModel::class, $id, 'Item Disposition');
    }
}
