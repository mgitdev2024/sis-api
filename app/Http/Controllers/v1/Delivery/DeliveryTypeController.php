<?php

namespace App\Http\Controllers\v1\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryType;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class DeliveryTypeController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($typeId = null)
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'type' => 'required|string|unique:delivery_types,type,' . $typeId,
            'description' => 'nullable|string',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(DeliveryType::class, $request, $this->getRules(), 'Delivery Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(DeliveryType::class, $request, $this->getRules($id), 'Delivery Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['type', 'description'];
        return $this->readPaginatedRecord(DeliveryType::class, $request, $searchableFields, 'Delivery Type');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(DeliveryType::class, $id, 'Delivery Type');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(DeliveryType::class, $id, 'Delivery Type');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(DeliveryType::class, $id, 'Delivery Type');
    }
}
