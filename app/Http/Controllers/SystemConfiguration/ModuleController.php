<?php

namespace App\Http\Controllers\SystemConfiguration;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration\Module;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ModuleController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'internal_system_id' => 'required|exists:internal_systems,id',
            'approval_workflow_id' => 'nullable|exists:approval_workflows,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(Module::class, $request, $this->getRules(), 'Module');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Module::class, $id, 'Module');
    }

    public function onGetAll()
    {
        return $this->readRecord(Module::class, 'Module');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Module::class, $request, $this->getRules(), 'Module', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(Module::class, $id, 'Module');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Module::class, $id, 'Module');
    }
}
