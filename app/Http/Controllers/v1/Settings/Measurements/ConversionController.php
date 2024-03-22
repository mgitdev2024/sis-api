<?php

namespace App\Http\Controllers\v1\Settings\Measurement;

use App\Http\Controllers\Controller;
use App\Models\Measurements\Conversion;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ConversionController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'primary_short_uom' => 'required|string',
            'secondary_short_uom' => 'required|string',
            'primary_long_uom' => 'required|string',
            'secondary_long_uom' => 'required|string',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(Conversion::class, $request, $this->getRules(), 'Conversions');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Conversion::class, $request, $this->getRules($id), 'Conversions', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecord(Conversion::class, $request, $searchableFields, 'Conversions');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Conversion::class, $id, 'Conversions');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Conversion::class, $id, 'Conversions');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(Conversion::class, $id, 'Conversions');
    }
}
