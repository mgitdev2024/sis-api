<?php

namespace App\Http\Controllers\v1\Settings\Measurements;

use App\Http\Controllers\Controller;
use App\Models\Settings\Measurements\UomModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class UomController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'short_uom' => 'required|string',
            'long_uom' => 'required|string',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(UomModel::class, $request, $this->getRules(), 'UOM');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(UomModel::class, $request, $this->getRules($id), 'UOM', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecord(UomModel::class, $request, $searchableFields, 'UOM');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(UomModel::class, $id, 'UOM');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(UomModel::class, $id, 'UOM');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(UomModel::class, $id, 'UOM');
    }
}
