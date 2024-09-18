<?php

namespace App\Http\Controllers\v1\Access;

use App\Http\Controllers\Controller;
use App\Models\Access\ScmSystemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;

class SCMSystemController extends Controller
{
    use ResponseTrait;
    public function onChangeStatus(Request $request, $system_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'status' => 'required|integer|in:1,2,3',
        ]);
        try {
            $system = ScmSystemModel::find($system_id);
            if (!$system) {
                return $this->dataResponse('error', 200, __('msg.record_not_found'));
            }
            $system->status = $fields['status'];
            $system->updated_by_id = $fields['created_by_id'];
            $system->save();
            return $this->dataResponse('success', 200, __('msg.update_success'), $system);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.update_failed'));
        }
    }

    public function onGet($system_id = null)
    {
        $systems = null;
        if ($system_id) {
            $systems = ScmSystemModel::find($system_id);
        } else {
            $systems = ScmSystemModel::all();
        }

        return $this->dataResponse('success', 200, __('msg.record_found'), $systems);
    }
}
