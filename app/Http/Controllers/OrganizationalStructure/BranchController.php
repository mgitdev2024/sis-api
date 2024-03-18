<?php

namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\Branch;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class BranchController extends Controller
{
    use CrudOperationsTrait;
    public Branch $branch;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'branch_code' => 'required|string',
            'branch_name' => 'required|string',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(Branch::class, $request, $this->getRules(), 'Branch');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Branch::class, $request, $this->getRules(), 'Branch', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['branch_code', 'branch_name'];
        return $this->readPaginatedRecord(Branch::class, $request, $searchableFields, 'Branch');
    }
    public function onGetAll()
    {
        return $this->readRecord(Branch::class, 'Branch');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Branch::class, $id, 'Branch');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(Branch::class, $id, 'Branch');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Branch::class, $id, 'Branch');
    }
}

