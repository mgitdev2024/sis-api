<?php

namespace App\Http\Controllers\v1\History;

use App\Http\Controllers\Controller;
use App\Models\History\PrintHistoryModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Exception;
use DB;
use Storage;

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
            'item_disposition_id' => 'nullable|integer',
            'created_by_id' => 'required|integer'
        ];
    }
    public function onCreate(Request $request)
    {
        $this->authenticateToken($request->bearerToken());
        $fields = $request->validate($this->getRules());

        try {
            DB::beginTransaction();
            $record = new PrintHistoryModel();
            $record->fill($fields);


            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('public/attachments/print-history');
                $filepath = 'storage/' . substr($attachmentPath, 7);
                $record->attachment = $filepath;
            }

            $record->save();
            DB::commit();
            return $this->dataResponse('success', 201, 'Print History ' . __('msg.create_success'), $record);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }
    public function onGetAll(Request $request)
    {
        return $this->readRecord(PrintHistoryModel::class,$request, 'Print History');
    }

    public function onGetCurrent(Request $request,$id)
    {
        $whereFields = [];
        if ($id != null) {
            $whereFields = [
                'production_batch_id' => $id
            ];
        }
        return $this->readCurrentRecord(PrintHistoryModel::class, $id, $whereFields, null, null,$request, 'Print History');
    }
    public function onGetById(Request $request,$id)
    {
        return $this->readRecordById(PrintHistoryModel::class, $id, $request,'Print History');
    }
}
