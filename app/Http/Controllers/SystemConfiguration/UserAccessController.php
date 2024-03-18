<?php

namespace App\Http\Controllers\SystemConfiguration;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration\UserAccess;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class UserAccessController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules()
    {
        return $rules = [
            'created_by_id' => 'required|exists:personal_informations,id',
            'credential_id' => 'required|exists:personal_informations,id',
            'access_management_id' => 'required|exists:access_managements,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(UserAccess::class, $request, $this->getRules(), 'User Access');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(UserAccess::class, $id, 'User Access');
    }

    public function onGetAll()
    {
        return $this->readRecord(UserAccess::class, 'User Access');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(UserAccess::class, $request, $this->getRules(), 'User Access', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(UserAccess::class, $id, 'User Access');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(UserAccess::class, $id, 'User Access');
    }
}
