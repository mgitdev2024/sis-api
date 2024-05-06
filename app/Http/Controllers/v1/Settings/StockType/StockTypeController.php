<?php

namespace App\Http\Controllers\v1\Settings\StockType;

use App\Http\Controllers\Controller;
use App\Models\Settings\StockTypeModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class StockTypeController extends Controller
{
    //
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function getRules()
    {
        return [
            // |exists:personal_informations,id
            'created_by_id' => 'required',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'status' => 'nullable|integer',

        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(StockTypeModel::class, $request, $this->getRules(), 'Stock Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(StockTypeModel::class, $request, $this->getRules(), 'Stock Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name', 'description'];
        return $this->readPaginatedRecord(StockTypeModel::class, $request, $searchableFields, 'Stock Type');
    }
    public function onGetall(Request $request)
    {
        return $this->readRecord(StockTypeModel::class, $request, 'Stock Type');
    }
    public function onGetById(Request $request,$id)
    {
        return $this->readRecordById(StockTypeModel::class, $id, $request, 'Stock Type');
    }
    public function onDeleteById(Request $request,$id)
    {
        return $this->deleteRecordById(StockTypeModel::class, $id, $request, 'Stock Type');
    }
}
