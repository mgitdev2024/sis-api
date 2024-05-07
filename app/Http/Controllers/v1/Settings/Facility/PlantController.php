<?php

namespace App\Http\Controllers\v1\Settings\Facility;

use App\Http\Controllers\Controller;
use App\Models\Settings\Facility\PlantModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class PlantController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'short_name' => 'required|string|unique:plants,short_name,' . $itemId,
            'long_name' => 'required|string|unique:plants,long_name,' . $itemId,
            'description' => 'nullable|string',
            'plant_code' => 'required|string'
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(PlantModel::class, $request, $this->getRules(), 'Plant');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(PlantModel::class, $request, $this->getRules($id), 'Plant', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['short_name', 'long_name'];
        return $this->readPaginatedRecord(PlantModel::class, $request, $searchableFields, 'Plant');
    }
    public function onGetall()
    {
        return $this->readRecord(PlantModel::class,  'Plant');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(PlantModel::class, $id,  'Plant');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(PlantModel::class, $id,  'Plant');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(PlantModel::class, $id,  'Plant');
    }
}
