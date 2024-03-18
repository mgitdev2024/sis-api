<?php

namespace App\Http\Controllers\SystemConfiguration;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration\SubModule;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class SubModuleController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'module_id' => 'required|exists:modules,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(SubModule::class, $request, $this->getRules(), 'Sub Module');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(SubModule::class, $id, 'Sub Module');
    }

    public function onGetAll()
    {
        return $this->readRecord(SubModule::class, 'Sub Module');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(SubModule::class, $request, $this->getRules(), 'Sub Module', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(SubModule::class, $id, 'Sub Module');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(SubModule::class, $id, 'Sub Module');
    }
}
