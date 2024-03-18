<?php

namespace App\Http\Controllers\SystemConfiguration;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration\InternalSystem;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class InternalSystemController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'short_name' => 'required|string',
            'long_name' => 'required|string',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(InternalSystem::class, $request, $this->getRules(), 'Internal System');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(InternalSystem::class, $id, 'Internal System');
    }

    public function onGetAll()
    {
        return $this->readRecord(InternalSystem::class, 'Internal System');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(InternalSystem::class, $request, $this->getRules(), 'Internal System', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(InternalSystem::class, $id, 'Internal System');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(InternalSystem::class, $id, 'Internal System');
    }
}
