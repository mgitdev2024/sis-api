<?php

namespace App\Http\Controllers\Bulk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseTrait;
use App\Traits\MailTrait;
use App\Http\Controllers\Auth\SignedUrlController;
use App\Models\Credential;
use App\Models\ContactNumber;
use App\Models\EmergencyContact;
use App\Models\EmploymentInformation;
use App\Models\GovernmentInformation;
use App\Models\PersonalInformation;

class BulkController extends Controller
{
    use ResponseTrait;
    use MailTrait;
    public Credential $credential;
    public PersonalInformation $personalInformation;
    public ContactNumber $contactNumber;
    public EmergencyContact $emergencyContact;
    public GovernmentInformation $governmentInformation;
    public EmploymentInformation $employedInformation;
    public function onBulkUploadEmployee(Request $request)
    {
        try {
            DB::beginTransaction();

            $file = $request['file'];
            $currentTime = now();
            foreach (json_decode($file) as $row) {
                $this->credential = new Credential();
                $this->credential->employee_id = $row->employee_id;
                $this->credential->status = 1;
                $this->credential->created_at = $currentTime;
                $this->credential->updated_at = $currentTime;
                $this->credential->save();

                $this->personalInformation = new PersonalInformation();
                $this->personalInformation->employee_id = $row->employee_id;
                $this->personalInformation->first_name = $row->first_name;
                $this->personalInformation->middle_name = $row->middle_name;
                $this->personalInformation->last_name = $row->last_name;
                $this->personalInformation->prefix = $row->prefix;
                $this->personalInformation->suffix = $row->suffix;
                $this->personalInformation->gender = $row->gender;
                $this->personalInformation->birth_date = \DateTime::createFromFormat('n/j/Y', $row->birth_date)->format('Y-n-j');
                $this->personalInformation->age = $row->age;
                $this->personalInformation->marital_status = $row->marital_status;
                $this->personalInformation->personal_email = $row->personal_email;
                $this->personalInformation->company_email = $row->company_email;
                $this->personalInformation->created_at = $currentTime;
                $this->personalInformation->updated_at = $currentTime;
                $this->personalInformation->save();

                $this->contactNumber = new ContactNumber();
                $this->contactNumber->personal_information_id = $this->personalInformation->id;
                $this->contactNumber->phone_number = $row->personal_contact_number;
                $this->contactNumber->type = 0;
                $this->contactNumber->status = 1;
                $this->contactNumber->created_at = $currentTime;
                $this->contactNumber->updated_at = $currentTime;
                $this->contactNumber->save();

                $this->emergencyContact = new EmergencyContact();
                $this->emergencyContact->personal_information_id = $this->personalInformation->id;
                $this->emergencyContact->name = $row->emergency_contact_full_name;
                $this->emergencyContact->phone_number = $row->emergency_contact_phone_number;
                $this->emergencyContact->relationship = $row->emergency_contact_relationship;
                $this->emergencyContact->status = 1;
                $this->emergencyContact->created_at = $currentTime;
                $this->emergencyContact->updated_at = $currentTime;
                $this->emergencyContact->save();

                $this->governmentInformation = new GovernmentInformation();
                $this->governmentInformation->personal_information_id = $this->personalInformation->id;
                $this->governmentInformation->sss_number = $row->sss_number;
                $this->governmentInformation->philhealth_number = $row->philhealth_number;
                $this->governmentInformation->pagibig_number = $row->pagibig_number;
                $this->governmentInformation->tin_number = $row->tin_number;
                $this->governmentInformation->created_at = $currentTime;
                $this->governmentInformation->updated_at = $currentTime;
                $this->governmentInformation->save();

                $this->employedInformation = new EmploymentInformation();
                $this->employedInformation->personal_information_id = $this->personalInformation->id;
                $this->employedInformation->company_id = $row->company;
                $this->employedInformation->branch_id = $row->branch;
                $this->employedInformation->department_id = $row->department;
                $this->employedInformation->section_id = $row->section;
                $this->employedInformation->position_id = $row->position !=  ' ' ? null : $row->position  ;
                $this->employedInformation->workforce_division_id = $row->workforce_division;
                $this->employedInformation->employment_classification = $row->employment_classification;
                $this->employedInformation->date_hired = \DateTime::createFromFormat('n/j/Y', $row->date_hired)->format('Y-n-j');
                $this->employedInformation->onboarding_status = $row->onboarding_status;
                $this->employedInformation->created_at = $currentTime;
                $this->employedInformation->updated_at = $currentTime;
                $this->employedInformation->save();
                // api
                $credentialQuery = Credential::where('employee_id', $row->employee_id);
                $signedController = new SignedUrlController();
                $temporaryUrl = $signedController->onCreateSignedUrl($credentialQuery, 'create', 'password/create');
                $full_name = $row->first_name . ' ' . $row->last_name;
                $this->onSendSignedUrl($row->personal_email, 'create', $full_name, $temporaryUrl);
                DB::commit();

                /* $body = [
                    "apiKey" => "SWPG6BJaxZ0IjfRV1K1SAQvOiVbQuY",
                    "apiSecret" => "_-0Ww33ewrXppW2I_U8LzH7aUma_JOmr",
                    "from" => "PTXTrial",
                    "to" => $row->personal_contact_number,
                    "text" => "Hello! You've been successfully registered with One Mary Grace.Your account has been created, and your login credentials are awaiting you in either your personal or company email inbox."
                ];
                Http::post('https://api.promotexter.com/sms/send', $body); */
            }
            /*  foreach ($dataToInsert as $row) {
                $credentialData[] = [
                    'employee_id' => $row['employee_id'],
                    'password' => $row['password'],
                    'status' => $row['status'],
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime,
                ];
                $personalInformationData[] = [
                    'employee_id' => $row['employee_id'],
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'],
                    'last_name' => $row['last_name'],
                    'status' => $row['status'],
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime,
                ];
            }
            Credential::insert($credentialData);
            PersonalInformation::insert($personalInformationData); */
            return $this->dataResponse('success', 200, __('msg.bulk_upload_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    
}
