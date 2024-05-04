<?php

namespace App\Http\Controllers\v1\History;

use App\Http\Controllers\Controller;
use App\Models\History\PrintHistoryModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Exception;

class PrintHistoryController extends Controller
{
    use CrudOperationsTrait;

    public function getRules()
    {
        return [
            'production_batch_id' => 'required|integer',
            'produced_items' => 'required|string',
            'reason' => 'nullable|string',
            'attachment' => 'nullable',
            'is_reprint' => 'required|boolean',
            'is_endorsed_by_qa' => 'nullable|boolean',
            'item_disposition_id' => 'nullable|integer',
            'created_by_id' => 'required|integer'
        ];
    }
    public function onCreate(Request $request)
    {
        $this->authenticateToken($request->bearerToken());
        $fields = $request->validate($this->getRules());
        if ($request->hasFile('attachment')) {
            dd($request->file('attachment'));
            $attachmentPath = $request->file('attachment')->store('attachments');
        }
        dd('wsal akng napasa');
        try {
            $record = new PrintHistoryModel();
            $record->fill($fields);
            $record->save();
            return $this->dataResponse('success', 201, 'Print History ' . __('msg.create_success'), $record);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }
    public function onGetAll(Request $request)
    {
        return $this->readRecord(PrintHistoryModel::class,$request, 'Print History');
    }

    public function onGetCurrent($id,Request $request)
    {
        $whereFields = [];
        if ($id != null) {
            $whereFields = [
                'production_batch_id' => $id
            ];
        }
        return $this->readCurrentRecord(PrintHistoryModel::class, $id, $whereFields, null, null,$request, 'Print History');
    }
    public function onGetById($id,Request $request)
    {
        return $this->readRecordById(PrintHistoryModel::class, $id, $request,'Print History');
    }
}
