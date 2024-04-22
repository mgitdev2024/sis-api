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
        return $this->createRecord(PrintHistoryModel::class, $request, $this->getRules(), 'Print History');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(PrintHistoryModel::class, $request, $this->getRules(), 'Print History', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['production_batch_id'];
        return $this->readPaginatedRecord(PrintHistoryModel::class, $request, $searchableFields, 'Print History');
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
    public function onGetAll()
    {
        return $this->readRecord(PrintHistoryModel::class, 'Print History');
    }
    public function onGetCurrent($id)
    {
        $whereFields = [];
        if ($id != null) {
            $whereFields = [
                'production_batch_id' => $id
            ];
        }
        return $this->readCurrentRecord(PrintHistoryModel::class, $id, $whereFields, null, null, 'Print History');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(PrintHistoryModel::class, $id, 'Print History');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(PrintHistoryModel::class, $id, 'Print History');
    }
}
