<?php

namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\Company;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class CompanyController extends Controller
{
    use CrudOperationsTrait;
    public Company $company;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'company_code' => 'required|string',
            'company_short_name' => 'nullable|string',
            'company_long_name' => 'required|string',
            'company_level' => 'nullable|string',
            'tin_no' => 'required|string',
            'sec_no' => 'required|string',
            'sec_registered_date' => 'required|date',
            'registered_address' => 'required|string',
            'transactional_considerations' => 'nullable|string',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        if (!$request['company_short_name']) {
            $generateShortName = $this->onGenerateShortName($request['company_long_name']);
            $request['company_short_name'] = $generateShortName;
        }
        return $this->createRecord(Company::class, $request, $this->getRules(), 'Company');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Company::class, $request, $this->getRules(), 'Company', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['company_code', 'company_short_name', 'company_long_name', 'company_level'];
        return $this->readPaginatedRecord(Company::class, $request, $searchableFields, 'Company');
    }
    public function onGetAll()
    {
        return $this->readRecord(Company::class, 'Company');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Company::class, $id, 'Company');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(Company::class, $id, 'Company');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Company::class, $id, 'Company');
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
}

