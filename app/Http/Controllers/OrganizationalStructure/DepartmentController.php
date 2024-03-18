<?php

namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\Department;
use App\Models\OrganizationalStructure\Division;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Illuminate\Support\Facades\DB;
use Exception;


class DepartmentController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules()
    {
        $rules = [
            'created_by_id' => 'required|exists:personal_informations,id',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'department_code' => 'required|string|max:10',
            'department_short_name' => 'nullable|string|max:50',
            'department_long_name' => 'required|string|max:50',
            'status' => 'nullable|integer',
        ];
        return $rules;
    }
    public function onCreate(Request $request)
    {
        if (!$request['department_short_name']) {
            $generateShortName = $this->onGenerateShortName($request['department_long_name']);
            $request['department_short_name'] = $generateShortName;
        }
        return $this->createRecord(Department::class, $request, $this->getRules(), 'Department');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Department::class, $request, $this->getRules(), 'Department', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['department_code', 'department_short_name', 'department_long_name'];
        return $this->readPaginatedRecord(Department::class, $request, $searchableFields, 'Department');
    }
    public function onGetAll()
    {
        return $this->readRecord(Department::class, 'Department');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Department::class, $id, 'Department');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Department::class, $id, 'Department');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(Department::class, $id, 'Department');
    }
    public function onGenerateShortName($data)
    {
        $explodeData = explode(' ', $data);
        $shortName = '';
        if (count($explodeData) > 1) {
            foreach ($explodeData as $title) {
                $shortName .= strtoupper($title[0]);
            }
        } else {
            for ($ctr = 0; $ctr <= strlen($data) - 1; $ctr++) {
                $shortName .= strtoupper($data[$ctr]);
                if (strlen($shortName) >= 3) {
                    break;
                }
            }
        }
        return $shortName;
    }

    public function onBulkUpload(Request $request)
    {
        try {
            DB::beginTransaction();
            $file = $request['file'];
            $created_by_id = $request['created_by_id'];
            $currentTime = now();
            $dataToInsert = json_decode($file, true);
            foreach ($dataToInsert as $row) {
                $listData[] = [
                    'created_by_id' => $created_by_id,
                    'division_id' => $this->onCheckDivisionCode($row['division_code'])->id,
                    'department_code' => $row['department_code'],
                    'department_short_name' => $row['department_short_name'],
                    'department_long_name' => $row['department_long_name'],
                    'status' => 1,
                    'created_at' => $currentTime,
                ];
            }
            Department::insert($listData);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.bulk_upload_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onCheckDivisionCode($division_code)
    {
        return Division::where('division_code', $division_code)->first();
    }
}
