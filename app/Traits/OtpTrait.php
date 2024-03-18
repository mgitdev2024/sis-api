<?php
namespace App\Traits;

use App\Models\ContactNumber;
use Exception;
use Http;

trait OtpTrait
{
    public static function onGenerateOtp()
    {
        return sprintf('%06d', mt_rand(0, 999999));
    }
    public static function onSaveOtp($phoneNumber)
    {
        try {
            $contactNumber = ContactNumber::where('phone_number', $phoneNumber)
                ->where('status', 1)
                ->first();
            $otp = OtpTrait::onGenerateOtp();
            if ($contactNumber) {
                $personalInformation = $contactNumber->personalInformation()->where('status', 1)->first();
                if ($personalInformation) {
                    $credentials = $personalInformation->credential;
                    $credentials->otp = $otp;
                    $credentials->save();
                    OtpTrait::onCreateMessageOtp($contactNumber->phone_number, $personalInformation->first_name, $otp);
                    $data['success'] = [
                        'message' => 'OTP successfully saved'
                    ];
                    return $data;
                }
                return false;
            }
            return false;
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }
    public static function onValidateOtpRequest($otp, $phoneNumber)
    {
        try {
            $contactNumber = ContactNumber::where('phone_number', $phoneNumber)
                ->where('status', 1)
                ->first();
            if ($contactNumber) {
                $credentials = $contactNumber->personalInformation->credential;
                $otpSaved = $contactNumber->personalInformation->credential->otp;
                if ($otp === $otpSaved) {
                    $data['success'] = [
                        'message' => 'OTP successfully validated'
                    ];
                    $credentials->otp = null;
                    $credentials->save();
                    return $data;
                }
                return false;
            }
            return false;
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }
    public static function onCreateMessageOtp($phoneNumber, $firstname, $otp)
    {
        $message = "Hi $firstname! Use this OTP [$otp] to reset your password. If you didn't request this, ignore the message. Best regards, One Mary Grace.";
        $body = [
            "apiKey" => env('PROMOTEXTER_KEY'),
            "apiSecret" => env('PROMOTEXTER_SECRET'),
            "from" => "PTXTrial",
            "to" => $phoneNumber,
            "text" => $message
        ];
        Http::post('https://api.promotexter.com/sms/send', $body);
    }
}
?>