<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ContactNumber;
use App\Models\Credential;
use App\Models\PersonalInformation;
use Illuminate\Http\Request;
use Exception;

use App\Traits\ResponseTrait;
use App\Traits\MailTrait;
use App\Traits\OtpTrait;

class SignedUrlController extends Controller
{
    use ResponseTrait;
    use MailTrait;
    use OtpTrait;

    public function onCheckToken(Request $request)
    {
        return $this->dataResponse('success', 200, __('msg.token_valid'));
    }
    public function onCheckSignedURL($token)
    {
        try {
            $credentialSignedRoute = Credential::where('signed_route', $token)->first();
            if (!$credentialSignedRoute) {
                return $this->dataResponse('error', 404, __('msg.token_invalid'));
            }
            return $this->dataResponse('success', 200, __('msg.token_valid'), $credentialSignedRoute);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, $exception->getMessage());
        }
    }
    public function onSendLoginURL(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|in:create,reset,lock,otp',
            'route' => 'required',
            'email' => 'nullable|email',
            'phone_number' => 'nullable'
        ]);
        try {
            if (isset($fields['email'])) {
                $personalInformation = PersonalInformation::where('personal_email', $fields['email'])
                    ->orWhere('company_email', $fields['email'])
                    ->first();
                if (!$personalInformation) {
                    return $this->dataResponse('error', 404, __('msg.email_not_found'));
                }
                $credentialQuery = Credential::where('employee_id', $personalInformation->employee_id);
                if ($fields['type'] == 'create') {
                    $credentialQuery->where('is_first_login', 1);
                }
                $temporaryUrl = $this->onCreateSignedUrl($credentialQuery, $fields['type'], $fields['route']);
                $full_name = $personalInformation->first_name . ' ' . $personalInformation->last_name;
                $this->onSendSignedUrl($fields['email'], $fields['type'], $full_name, $temporaryUrl);
                return $this->dataResponse('success', 200, __('msg.email_sent'));
            } else {
                $contactNumber = ContactNumber::where('phone_number', $fields['phone_number'])->first();
                $credentialQuery = $contactNumber->personalInformation->credential;
                $this->onCreateSignedUrl($credentialQuery, $fields['type'], $fields['route']);
                return $this->dataResponse('success', 200, __('msg.signed_url_register'));
                if (!$contactNumber) {
                    return $this->dataResponse('error', 404, __('msg.phone_not_found'));
                }
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onCreateSignedUrl($credentialQuery, $type, $route)
    {
        try {
            $credentialSignedRoute = $credentialQuery->first();
            if (!$credentialSignedRoute) {
                return $this->dataResponse('error', 404, __('msg.signed_url_invalid'));
            }
            $baseURL = env('BASE_URL');
            $token = $type . '|' . bin2hex(random_bytes(16));
            $temporaryUrl = $baseURL . $route . '/' . $token;
            $credentialSignedRoute->signed_route = $token;
            $credentialSignedRoute->save();
            return $temporaryUrl;
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onSendOtp(Request $request)
    {
        $fields = $request->validate([
            'phone_number' => 'required',
        ]);
        try {
            $phoneNumber = $fields['phone_number'];
            $saveOtp = $this->onSaveOtp($phoneNumber);
            if (isset($saveOtp['success'])) {
                return $this->dataResponse('success', 200, __('msg.otp_sent'));
            }
            return $this->dataResponse('error', 400, __('msg.otp_failed'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onValidateOtp(Request $request)
    {
        $fields = $request->validate([
            'otp' => 'required',
            'phone_number' => 'required',
        ]);
        try {
            $otp = $fields['otp'];
            $phoneNumber = $fields['phone_number'];
            $validatedOtp = $this->onValidateOtpRequest($otp, $phoneNumber);
            if (isset($validatedOtp['success'])) {
                return $this->dataResponse('success', 200, __('msg.otp_valid'));
            }
            return $this->dataResponse('error', 400, __('msg.otp_invalid'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
