<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseTrait;
use App\Models\Credential;

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

        if (!Auth::attempt($fields)) {
            return $this->dataResponse('error', 404, __('msg.employee_not_found'));
        }
        if ($logged_user->status == 0) {
            return $this->dataResponse('error', 404, 'Login failed: account has been suspended. Please contact IT Support for assistance.');
        }
        $token = auth()->user()->createToken('appToken')->plainTextToken;
        $data = [
            'token' => $token,
        ];
        return $this->dataResponse('success', 200, __('msg.login_success'), $data);
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
}
