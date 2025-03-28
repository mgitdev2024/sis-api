<?php

namespace App\Http\Controllers\v1\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Admin\System\AdminSystemModel;
use App\Traits\Admin\AdminTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;

class SCMSystemController extends Controller
{
    use ResponseTrait, AdminTrait;
    public function onChangeStatus(Request $request, $system_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'status' => 'required|integer|in:1,2,3',
        ]);
        try {
            $system = AdminSystemModel::find($system_id);
            if (!$system) {
                return $this->dataResponse('error', 200, __('msg.record_not_found'));
            }
            $system->status = $fields['status'];
            $system->updated_by_id = $fields['created_by_id'];
            $system->save();
            $this->onCreateAdminLogs($system_id, AdminSystemModel::class, $system->getAttributes(), 1, $fields['created_by_id']);
            return $this->dataResponse('success', 200, __('msg.update_success'), $system);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.update_failed'));
        }
    }

    public function onGet($system_id = null)
    {
        $systems = null;
        if ($system_id) {
            $systems = AdminSystemModel::find($system_id);
        } else {
            $systems = AdminSystemModel::all();
        }

        return $this->dataResponse('success', 200, __('msg.record_found'), $systems);
    }
}
