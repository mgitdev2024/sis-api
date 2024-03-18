<?php
namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\WorkforceDivision;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class WorkforceDivisionController extends Controller
{
    use CrudOperationsTrait;
    public WorkforceDivision $workforceDivision;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'workforce_division_code' => 'required|string',
            'workforce_division_name' => 'required|string',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(WorkforceDivision::class, $request, $this->getRules(), 'Workforce Division');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(WorkforceDivision::class, $request, $this->getRules(), 'Workforce Division', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['workforce_division_code', 'workforce_division_name'];
        return $this->readPaginatedRecord(WorkforceDivision::class, $request, $searchableFields, 'Workforce Division');
    }
    public function onGetAll()
    {
        return $this->readRecord(WorkforceDivision::class, 'Workforce Division');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WorkforceDivision::class, $id, 'Workforce Division');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(WorkforceDivision::class, $id, 'Workforce Division');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(WorkforceDivision::class, $id, 'Workforce Division');
    }
}

