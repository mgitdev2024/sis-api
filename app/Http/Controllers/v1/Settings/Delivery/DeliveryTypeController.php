<?php

namespace App\Http\Controllers\v1\Settings\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Settings\Delivery\DeliveryTypeModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class DeliveryTypeController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($typeId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'type' => 'required|string|unique:delivery_types,type,' . $typeId,
            'description' => 'nullable|string',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(DeliveryTypeModel::class, $request, $this->getRules(), 'Delivery Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(DeliveryTypeModel::class, $request, $this->getRules($id), 'Delivery Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['type', 'description'];
        return $this->readPaginatedRecord(DeliveryTypeModel::class, $request, $searchableFields, 'Delivery Type');
    }
    public function onGetall(Request $request)
    {
        return $this->readRecord(DeliveryTypeModel::class, $request, 'Delivery Type');
    }
    public function onGetById(Request $request, $id)
    {
        return $this->readRecordById(DeliveryTypeModel::class, $id, $request, 'Delivery Type');
    }
    public function onDeleteById(Request $request, $id)
    {
        return $this->deleteRecordById(DeliveryTypeModel::class, $id, $request, 'Delivery Type');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(DeliveryTypeModel::class, $id, $request, 'Delivery Type');
    }
}
