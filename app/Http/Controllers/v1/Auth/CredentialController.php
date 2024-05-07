<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseTrait;
use DB;

class CredentialController extends Controller
{
    use ResponseTrait;

    public function onLogin(Request $request)
    {
        $fields = $request->validate([
            'employee_id' => 'required',
            'position' => 'required|string',
            'user_access' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $userExist = User::where('employee_id', $fields['employee_id'])->first();

            if (!$userExist) {
                User::insert([
                    'employee_id' => $fields['employee_id'],
                    'position' => $fields['position'],
                    'user_access' => $fields['user_access'] ?? null,
                ]);
            }

            $userId = User::where('employee_id', $fields['employee_id'])->first()->id;
            Auth::loginUsingId($userId);
            $token = auth()->user()->createToken('appToken')->plainTextToken;
            DB::commit();
            $data = [
                'token' => $token,
            ];
            return $this->dataResponse('success', 200, __('msg.login_success'), $data);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
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

}
