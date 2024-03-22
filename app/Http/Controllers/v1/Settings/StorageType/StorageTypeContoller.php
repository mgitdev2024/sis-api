<?php

namespace App\Http\Controllers\v1\Settings\StorageType;

use App\Http\Controllers\Controller;
use App\Models\Settings\StorageTypeModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class StorageTypeContoller extends Controller
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
        return $this->createRecord(StorageTypeModel::class, $request, $this->getRules(), 'Storage Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(StorageTypeModel::class, $request, $this->getRules(), 'Storage Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name', 'description'];
        return $this->readPaginatedRecord(StorageTypeModel::class, $request, $searchableFields, 'Storage Type');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(StorageTypeModel::class, $id, 'Storage Type');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(StorageTypeModel::class, $id, 'Storage Type');
    }
}
