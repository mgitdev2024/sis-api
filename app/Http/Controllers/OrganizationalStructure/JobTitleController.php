<?php

namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\Department;
use App\Models\OrganizationalStructure\Division;
use App\Models\OrganizationalStructure\JobTitle;
use App\Models\OrganizationalStructure\Section;
use Illuminate\Http\Request;
use Exception;
use App\Traits\CrudOperationsTrait;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class JobTitleController extends Controller
{
    use CrudOperationsTrait;
    public JobTitle $jobTitle;

    protected function getRules()
    {
        $rules = [
            'created_by_id' => 'required|exists:personal_informations,id',
            'section_id' => 'nullable|exists:sections,id',
            'department_id' => 'nullable|exists:departments,id',
            'division_id' => 'nullable|exists:divisions,id',
            'job_code' => 'nullable|string|max:40',
            'job_title' => 'required|string|max:50',
            'job_description' => 'nullable|string',
            'slot' => 'required|integer',
            'status' => 'nullable|integer',
        ];
        return $rules;
    }

    public function onCreate(Request $request)
    {
        if (!$request['job_code'] && (!$request['section_id'] || !$request['division_id'])) {
            $unit = $request->filled('section_id') ? $request['section_id'] : ($request->filled('division_id') ? $request['division_id'] : null);
            $class = $request->filled('section_id') ? Section::class : ($request->filled('division_id') ? Division::class : null);
            $generateJobCode = $this->onGenerateJobCode($unit, $class, $request['job_title']);
            $request['job_code'] = $generateJobCode;
        }
        return $this->createRecord(JobTitle::class, $request, $this->getRules(), 'Job Title');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(JobTitle::class, $request, $this->getRules(), 'Job Title', $id);
    }

    public function onGetAll()
    {
        return $this->readRecord(JobTitle::class, 'Job Title');
    }

    public function onGetVacantSlot()
    {
        try {
            $dataList = JobTitle::where('slot', '>', 0)->get();


            $reconstructedList = [];
            foreach ($dataList as $key => $value) {
                $data = JobTitle::findOrFail($value->id);
                $response = $data->toArray();
                $response['created_by'] = $data->createdBy->first_name . ' ' . $data->createdBy->middle_name . ' ' . $data->createdBy->last_name;
                if (isset($data->updated_by_id)) {
                    $response['updated_by'] = $data->updatedBy->first_name . ' ' . $data->updatedBy->middle_name . ' ' . $data->updatedBy->last_name;
                }
                $reconstructedList[] = $response;
            }
            return $this->dataResponse('success', Response::HTTP_OK, __('msg.record_found'), $reconstructedList);
        } catch (Exception $exception) {
            return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }
    }

    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['job_code', 'job_title'];
        return $this->readPaginatedRecord(JobTitle::class, $request, $searchableFields, 'Job Title');
    }

    public function onGetById($id)
    {
        return $this->readRecordById(JobTitle::class, $id, 'Job Title');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(JobTitle::class, $id, 'JobTitle');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(JobTitle::class, $id, 'Job Title');
    }

    public function onGenerateJobCode($unitId, $class, $jobTitle)
    {
        try {
            $className = strtolower(class_basename($class)) . '_long_name';
            $unitClass = $class::findOrFail($unitId);
            $unit = $unitClass->$className;
            $explodeSectionData = explode(' ', $unit);
            $explodeJobData = explode(' ', $jobTitle);

            $unitCode = '';
            $jobCode = '';

            if (count($explodeSectionData) > 1) {
                foreach ($explodeSectionData as $title) {
                    $unitCode .= strtoupper($title[0]);
                }
            } else {
                for ($ctr = 0; $ctr <= strlen($unit) - 1; $ctr++) {
                    $unitCode .= strtoupper($unit[$ctr]);

                    if (strlen($unitCode) >= 3) {
                        break;
                    }
                }
            }

            if (count($explodeJobData) > 1) {
                foreach ($explodeJobData as $title) {
                    $jobCode .= strtoupper($title[0]);
                }
            } else {
                for ($ctr = 0; $ctr <= strlen($jobTitle) - 1; $ctr++) {
                    $jobCode .= strtoupper($jobTitle[$ctr]);

                    if (strlen($jobCode) >= 3) {
                        break;
                    }
                }
            }
            return $unitCode . "-" . $jobCode;
        } catch (Exception $exception) {
            throw new Exception("Error Processing Request: " . $exception, 1);
        }
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

                if (!$row['job_code'] && (!$row['section_code'] || !$row['division_code'])) {
                    $unit = $row->filled('section_code') ? $row['section_code'] : ($row->filled('division_code') ? $row['division_code'] : null);
                    $class = $row->filled('section_code') ? Section::class : ($row->filled('division_code') ? Division::class : null);
                    $generateJobCode = $this->onGenerateJobCode($unit, $class, $row['job_title']);
                    $row['job_code'] = $generateJobCode;
                }
                $listData[] = [
                    'created_by_id' => $created_by_id,
                    'section_id' => $row['section_code'] ? $this->onCheckSectionCode($row['section_code'])->id : null,
                    'division_id' => $row['division_code'] ? $this->onCheckDivisionCode($row['division_code'])->id : null,
                    'department_id' => $row['department_code'] ? $this->onCheckDepartmentCode($row['department_code'])->id : null,
                    'job_code' => $row['job_code'],
                    'job_title' => $row['job_title'],
                    'job_description' => $row['job_description'] ?? null,
                    'slot' => $row['slot'],
                    'status' => 1,
                    'created_at' => $currentTime,
                ];
            }
            JobTitle::insert($listData);
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
    public function onCheckSectionCode($section_code)
    {
        return Section::where('section_code', $section_code)->first();
    }
    public function onCheckDivisionCode($division_code)
    {
        return Division::where('division$division_code', $division_code)->first();
    }

}
