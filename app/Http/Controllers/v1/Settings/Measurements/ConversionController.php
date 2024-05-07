<?php

namespace App\Http\Controllers\v1\Settings\Measurements;

use App\Http\Controllers\Controller;
use App\Models\Settings\Measurements\ConversionModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ConversionController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'conversion_short_uom' => 'required|string',
            'conversion_long_uom' => 'required|string',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ConversionModel::class, $request, $this->getRules(), 'Conversions');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ConversionModel::class, $request, $this->getRules($id), 'Conversions', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecord(ConversionModel::class, $request, $searchableFields, 'Conversions');
    }
    public function onGetall()
    {
        return $this->readRecord(ConversionModel::class,'Conversions');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ConversionModel::class, $id,'Conversions');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ConversionModel::class, $id,'Conversions');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ConversionModel::class, $id,'Conversions');
    }
}
