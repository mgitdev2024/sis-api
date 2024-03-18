<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Traits\ResponseTrait;

use App\Models\Address;
use App\Models\Credential;
use App\Models\Beneficiary;
use App\Models\ContactNumber;
use App\Models\EducationalBackground;
use App\Models\EmergencyContact;
use App\Models\EmploymentInformation;
use App\Models\GovernmentInformation;
use App\Models\PersonalInformation;

class CredentialController extends Controller
{
    use ResponseTrait;
    public function onLogin(Request $request)
    {
        $fields = $request->validate([
            'employee_id' => 'required',
            'password' => 'required|min:6',
        ]);
        $logged_user = Credential::where('employee_id', '=', $fields['employee_id'])->first();
        if (!$logged_user) {
            return $this->dataResponse('error', 404, __('msg.employee_not_found'));
        }
        if (!Auth::attempt(['employee_id' => $fields['employee_id'], 'password' => $fields['password']]) || $logged_user->is_locked >= 5) {
            $maxAttempts = config('auth.max_login_attempts', 5);
            $remainingAttempts = $maxAttempts - $logged_user->is_locked;
            $logged_user->is_locked++;
            $logged_user->save();

            if ($logged_user->is_locked >= $maxAttempts) {
                $personalEmail = $logged_user->personalInformation->personal_email ?? null;
                if ($personalEmail && !$logged_user->signed_route) {
                    $this->onSendEmailBulk($personalEmail, 'lock', 'password/reset');
                }
                $message = "Your account has been locked after five(5) consecutive unsuccessful attempts.";
                return $this->dataResponse('error', 400, $message);
            }
            $message = "Invalid Credentials. You have $remainingAttempts login attempts remaining before your account is locked for security reasons.";
            return $this->dataResponse('error', 401, $message);
        }
        $token = auth()->user()->createToken('appToken')->plainTextToken;
        $personalInformation = $this->onGetPersonalInformation($fields['employee_id']);
        $data = [
            'token' => $token,
            'user_details' => $personalInformation,
            'address_details' => $this->onGetAddressInformation($personalInformation['id']),
            'contact_details' => $this->onGetContactInformation($personalInformation['id']),
            'beneficiary_details' => $this->onGetBeneficiaryInformation($personalInformation['id']),
            'government_details' => $this->onGetGovernmentInformation($personalInformation['id']),
            'educational_details' => $this->onGetEducationalInformation($personalInformation['id']),
            'employment_details' => $this->onGetEmploymentInformation($personalInformation['id']),
        ];
        return $this->dataResponse('success', 200, __('msg.login_success'), $data);
    }
    public function onGetPersonalInformation($employee_id)
    {
        return PersonalInformation::where('employee_id', '=', $employee_id)->first();
    }
    public function onGetAddressInformation($personal_information_id)
    {
        return Address::where('personal_information_id', '=', $personal_information_id)->get();
    }
    public function onGetContactInformation($personal_information_id)
    {
        return [
            'primary_contact' => ContactNumber::where('personal_information_id', '=', $personal_information_id)->get(),
            'emergency_contact' => EmergencyContact::where('personal_information_id', '=', $personal_information_id)->first(),
        ];
    }
    public function onGetBeneficiaryInformation($personal_information_id)
    {
        return Beneficiary::where('personal_information_id', '=', $personal_information_id)->get();
    }
    public function onGetGovernmentInformation($personal_information_id)
    {
        return GovernmentInformation::where('personal_information_id', '=', $personal_information_id)->first();
    }
    public function onGetEducationalInformation($personal_information_id)
    {
        return EducationalBackground::where('personal_information_id', '=', $personal_information_id)->get();
    }
    public function onGetEmploymentInformation($personal_information_id)
    {
        return EmploymentInformation::where('personal_information_id', '=', $personal_information_id)->first();
    }
    public function onChangePassword(Request $request)
    {
        $fields = $request->validate([
            'employee_id' => 'required',
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|min:6|same:new_password',
        ]);
        try {
            $user = auth()->user();
            if (!Hash::check($request->old_password, $user->password)) {
                return $this->dataResponse('error', 400, __('msg.old_password_incorrect'));
            }
            $user->update([
                'password' => bcrypt($fields['new_password']),
            ]);
            return $this->dataResponse('success', 201, __('msg.password_change_successful'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.password_change_unsuccessful'));
        }
    }
    public function onLogout()
    {
        try {
            auth()->user()->tokens()->delete();
            return $this->dataResponse('success', 200, __('msg.logout'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onResetPassword(Request $request)
    {
        $fields = $request->validate([
            'employee_id' => 'required',
            'password' => 'required|confirmed|min:6',
            'password_confirmation' => 'required|min:6',
        ]);
        try {
            $credentialToUpdate = Credential::where('employee_id', $fields['employee_id'])->first();
            $isNotFirstLogin = $credentialToUpdate->is_first_login == 0;
            $signedRoute = explode($credentialToUpdate->signed_route, '|')[0];
            if (!$credentialToUpdate || ($isNotFirstLogin && $signedRoute == 'create')) {
                return $this->dataResponse('error', 400, __('msg.password_change_unsuccessful'));
            }
            $credentialToUpdate->password = bcrypt($request->password);
            $credentialToUpdate->is_first_login = 0;
            $credentialToUpdate->is_locked = 0;
            $credentialToUpdate->signed_route = null;
            $credentialToUpdate->update();
            return $this->dataResponse('success', 201, __('msg.password_change_successful'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
