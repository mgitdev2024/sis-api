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
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
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
    public function onGetall(Request $request)
    {
        return $this->readRecord(UomModel::class, $request, 'UOM');
    }
    public function onGetById(Request $request, $id)
    {
        return $this->readRecordById(UomModel::class, $id, $request, 'UOM');
    }
    public function onDeleteById(Request $request, $id)
    {
        return $this->deleteRecordById(UomModel::class, $id, $request, 'UOM');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(UomModel::class, $id, $request, 'UOM');
    }
}
