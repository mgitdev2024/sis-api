<?php

namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\JobTitle;
use App\Models\OrganizationalStructure\OrganizationalStructure;
use App\Models\EmploymentInformation;
use Exception;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use DB;
use App\Traits\ResponseTrait;

class OrganizationalStructureController extends Controller
{
    use ResponseTrait;
    use CrudOperationsTrait;
    public OrganizationalStructure $organizationalStructure;
    public $positionTree = [];
    public static function getRules()
    {
        $rules = [
            'created_by_id' => 'required|exists:personal_informations,id',
            'section_id' => 'nullable|integer|exists:sections,id',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'job_id' => 'nullable|integer',
            'workforce_division_id' => 'nullable|integer',
            'status' => 'required|integer',
            // Additional
            'based_on_position' => 'nullable|integer|exists:organizational_structure,id',
            'slot' => 'nullable|integer'
        ];
        return $rules;
    }
    public function onCreate(Request $request)
    {

        $fields = $request->validate($this->getRules());

        try {
            $jobSlot = $fields['slot'];

            for ($ctr = 0; $ctr < intval($jobSlot); $ctr++) {
                $this->organizationalStructure = new OrganizationalStructure();
                $jobPosition = null;

                $basedOnPosition = $fields['based_on_position'];
                $parentId = null;
                $level = 0;
                if ($basedOnPosition) {
                    $parent = OrganizationalStructure::find($basedOnPosition);
                    $parentId = $parent->id;
                    $level = ++$parent->level;
                }

                DB::beginTransaction();
                $this->organizationalStructure->fill($fields);
                $this->organizationalStructure->parent_id = $parentId;
                $this->organizationalStructure->level = $level;
                $this->organizationalStructure->save();
                if ($fields['job_id']) {
                    $jobPosition = JobTitle::find($fields['job_id']);
                    $jobPosition->slot = --$jobPosition->slot;
                    $jobPosition->save();
                }
                DB::commit();
            }

            if ($fields['slot'] <= 0) {
                $message = 'Organizational Structure ' . __('msg.create_failed');
                return $this->dataResponse('error', 404, $message);
            }
            $message = 'Organizational Structure ' . __('msg.create_success');
            return $this->dataResponse('success', 201, $message);

        } catch (Exception $exception) {
            DB::rollBack();
            $message = 'Error in adding Organizational Structure: ' . $exception->getMessage();
            return $this->dataResponse('error', 400, $message);
        }

    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(OrganizationalStructure::class, $request, $this->getRules(), 'Organizational Structure', $id);
    }

    public function onGetById($id)
    {
        try {
            $orgUnit = OrganizationalStructure::findOrFail($id);
            $employeeInformation = EmploymentInformation::where('position_id', $orgUnit->id)->first();
            $data = [
                'organizationalStructure' => $orgUnit->toArray(),
                'division' => $orgUnit->division,
                'department' => $orgUnit->department,
                'section' => $orgUnit->section,
                'employeeInformation' => $employeeInformation ? $employeeInformation->toArray() : null,
                'personal_information' => $employeeInformation->personalInformation ?? null,
                'children' => $orgUnit->children,
            ];
            $message = 'Organizational Structure ' . __('msg.record_found');
            return $this->dataResponse('success', 200, $message, $data);
        } catch (Exception $exception) {
            $message = 'Error in adding Organizational Structure: ' . $exception->getMessage();
            return $this->dataResponse('error', 400, $message);
        }
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(OrganizationalStructure::class, $id, 'Organizational Structure');
    }
    public function onDeleteById($id)
    {
        try {
            $deletedRows = OrganizationalStructure::find($id);
            if ($deletedRows && $deletedRows->children()->count() <= 0) {
                DB::beginTransaction();
                $jobPosition = JobTitle::find($deletedRows->job_id);
                if ($jobPosition) {
                    $jobPosition->slot = ++$jobPosition->slot;
                    $jobPosition->save();
                }
                $deletedRows->delete();
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }
            return $this->dataResponse('error', 404, 'Organizational Structure ' . __('msg.delete_failed'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetAll()
    {
        $organizations = OrganizationalStructure::with('children')->whereNull('parent_id')->get();
        $formattedData = $this->transformToJSON($organizations);
        $message = 'Organizational Structure ' . __('msg.record_found');
        return $this->dataResponse('success', 200, $message, $formattedData);
    }

    protected function transformToJSON($organizations)
    {
        return $organizations->map(function ($organization) {
            $children = $this->transformToJSON($organization->children);
            $parent = null;
            if ($organization->parent) {
                $parent = [
                    'parent_details' => $organization->parent,
                    'division' => $organization->parent->division,
                    'department' => $organization->parent->department,
                    'section' => $organization->parent->section,
                ];
            }
            $employeeInformation = EmploymentInformation::where('position_id', $organization->id)->first();
            return [
                'id' => $organization->id,
                'employment_data' => $employeeInformation ? $employeeInformation->toArray() : null,
                'personal_information' => $employeeInformation->personalInformation ?? null,
                'division' => $organization->division ?? null,
                'department' => $organization->department ?? null,
                'section' => $organization->section ?? null,
                'job_title' => $organization->jobTitle->job_title ?? null,
                'workforce_division_name' => $organization->workforceDivision->workforce_division_name ?? null,
                'color' => $this->onGenerateColor($organization->department->department_code ?? null),
                'children' => $children,
                'parent' => $parent,
            ];
        });
    }


    public function onGenerateColor($departmentName)
    {
        if ($departmentName != null) {
            $hash = md5($departmentName);
            $r = hexdec(substr($hash, 0, 2));
            $g = hexdec(substr($hash, 2, 2));
            $b = hexdec(substr($hash, 4, 2));

            $brightnessFactor = 1;
            $r = min(255, $r * $brightnessFactor);
            $g = min(255, $g * $brightnessFactor);
            $b = min(255, $b * $brightnessFactor);

            return ['r' => (int) $r, 'g' => (int) $g, 'b' => (int) $b];
        }
        return ['r' => 140, 'g' => 0, 'b' => 0]; //Default Maroon Color #8c0000
    }
}
