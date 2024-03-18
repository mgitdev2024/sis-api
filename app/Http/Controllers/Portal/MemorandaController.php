<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\Memoranda;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class MemorandaController extends Controller
{
    use CrudOperationsTrait;
    public Memoranda $memoranda;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'reference_number' => 'required|string',
            'subject' => 'required|string',
            'description' => 'required|string',
            'from' => 'required|string',
            'to' => 'required|string',
            'effective_date' => 'date',
            'file' => 'string|nullable',
            'status' => 'nullable|integer',
            'is_pinned' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(Memoranda::class, $request, $this->getRules(), 'Memoranda');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Memoranda::class, $request, $this->getRules(), 'Memoranda', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'subject'];
        return $this->readPaginatedRecord(Memoranda::class, $request, $searchableFields, 'Memoranda');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Memoranda::class, $id, 'Memoranda');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Memoranda::class, $id, 'Memoranda');
    }
}
