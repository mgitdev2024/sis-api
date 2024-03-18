<?php

namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\Department;
use Illuminate\Http\Request;
use App\Models\OrganizationalStructure\Section;
use App\Traits\CrudOperationsTrait;
use Illuminate\Support\Facades\DB;
use Exception;
class SectionController extends Controller
{
    use CrudOperationsTrait;
    public Section $section;
    public static function getRules()
    {
        $rules = [
            'created_by_id' => 'required|exists:personal_informations,id',
            'department_id' => 'required|integer|exists:departments,id',
            'section_code' => 'required|string|max:10',
            'section_short_name' => 'nullable|string|max:50',
            'section_long_name' => 'required|string|max:50',
            'status' => 'nullable|integer',
        ];
        return $rules;
    }
    public function onCreate(Request $request)
    {
        if (!$request['section_short_name']) {
            $generateShortName = $this->onGenerateShortName($request['section_long_name']);
            $request['section_short_name'] = $generateShortName;
        }
        return $this->createRecord(Section::class, $request, $this->getRules(), 'Section');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Section::class, $request, $this->getRules(), 'Section', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['section_code', 'section_short_name', 'section_long_name'];
        return $this->readPaginatedRecord(Section::class, $request, $searchableFields, 'Section');
    }
    public function onGetAll()
    {
        return $this->readRecord(Section::class, 'Section');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Section::class, $id, 'Section');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Section::class, $id, 'Section');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(Section::class, $id, 'Section');
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
                if (!$row['section_short_name']) {
                    $row['section_short_name'] = $this->onGenerateShortName($row['section_long_name']);
                }
                $listData[] = [
                    'created_by_id' => $created_by_id,
                    'department_id' => $this->onCheckDepartmentCode($row['department_code'])->id,
                    'section_code' => $row['section_code'],
                    'section_short_name' => $row['section_short_name'],
                    'section_long_name' => $row['section_long_name'],
                    'status' => 1,
                    'created_at' => $currentTime,
                ];
            }
            Section::insert($listData);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.bulk_upload_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onCheckDepartmentCode($department_code)
    {
        return Department::where('department_code', $department_code)->first();
    }
}
