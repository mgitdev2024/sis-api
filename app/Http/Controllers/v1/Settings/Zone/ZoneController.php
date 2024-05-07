<?php

namespace App\Http\Controllers\v1\Settings\Zone;

use App\Http\Controllers\Controller;
use App\Models\Settings\ZoneModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
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
        return $this->createRecord(ZoneModel::class, $request, $this->getRules(), 'Zone');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ZoneModel::class, $request, $this->getRules(), 'Zone', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name', 'description'];
        return $this->readPaginatedRecord(ZoneModel::class, $request, $searchableFields, 'Zone');
    }
    public function onGetall()
    {
        return $this->readRecord(ZoneModel::class,'Zone');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ZoneModel::class, $id,'Zone');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ZoneModel::class, $id,'Zone');
    }
}
