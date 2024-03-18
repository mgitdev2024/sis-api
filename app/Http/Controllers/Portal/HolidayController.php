<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\Holiday;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class HolidayController extends Controller
{
    use CrudOperationsTrait;
    public Holiday $holiday;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'date' => 'required|date',
            'is_local' => 'nullable|integer',
            'is_special' => 'nullable|integer',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(Holiday::class, $request, $this->getRules(), 'Holiday');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Holiday::class, $request, $this->getRules(), 'Holiday', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['title', 'location'];
        return $this->readPaginatedRecord(Holiday::class, $request, $searchableFields, 'Holiday');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Holiday::class, $id, 'Holiday');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Holiday::class, $id, 'Holiday');
    }
}
