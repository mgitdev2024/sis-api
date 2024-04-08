<?php

namespace App\Http\Controllers\v1\History;

use App\Http\Controllers\Controller;
use App\Models\History\PrintHistoryModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;

class PrintHistoryController extends Controller
{
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function getRules()
    {
        return [
            // |exists:personal_informations,id
            'created_by_id' => 'required',
            'production_batch_id' => 'required|integer',
            'produce_items' => 'nullable|string|max:255',
            'is_reprint' => 'nullable|integer',
            'reason' => 'nullable|string|max:255',
            'attachment' => 'nullable|string',
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
    }
    public function onGetAll()
    {
        return $this->readRecord(PrintHistoryModel::class, 'Print History');
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
