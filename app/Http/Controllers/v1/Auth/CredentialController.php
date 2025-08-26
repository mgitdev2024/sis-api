<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Support\Facades\Cache;

class CredentialController extends Controller
{
    use ResponseTrait;

    public function onLogin(Request $request)
    {
        $fields = $request->validate([
            'employee_id' => 'required',
            'prefix' => 'nullable|string',
            'first_name' => 'nullable|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'suffix' => 'nullable|string',
            'position' => 'nullable|string',
            'user_access' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $userExist = User::where('employee_id', $fields['employee_id'])->first();
            if (!$userExist) {
                User::insert([
                    'employee_id' => $fields['employee_id'],
                    'prefix' => $fields['prefix'] ?? null,
                    'first_name' => $fields['first_name'] ?? null,
                    'middle_name' => $fields['middle_name'] ?? null,
                    'last_name' => $fields['last_name'] ?? null,
                    'suffix' => $fields['suffix'] ?? null,
                    'position' => $fields['position'] ?? null,
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
            Cache::forget('store_' . auth()->id());
            auth()->user()->tokens()->delete();
            return $this->dataResponse('success', 200, __('msg.logout'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onCheckToken()
    {
        try {

            // if ($instance = PersonalAccessToken::find($id)) {
            //     $checkToken = hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
            // }

            // $data = [
            //     'status' => false,
            //     'message' => 'Token is invalid'
            // ];
            // $checkToken = PersonalAccessToken::findToken($token);
            // if ($checkToken && $checkToken->tokenable) {
            //     $data['status'] = true;
            //     $data['message'] = 'Token is valid';
            //     return response()->json($data);
            // } else {
            //     return response()->json($data, 401);
            // }
            return response()->json('success');
        } catch (Exception $exception) {
            \Log::info($exception);
            $data = [
                'status' => false,
                'message' => 'Token is invalid'
            ];
            return response()->json($data, 401);
        }
    }

    public function onStoreCache(Request $request)
    {
        $fields = $request->validate([
            'store_code' => 'required|string',
            'sub_unit' => 'nullable|string',
        ]);

        try {
            Cache::put('store_' . auth()->id(), [
                'store_code' => $fields['store_code'],
                'sub_unit' => $fields['sub_unit'],
            ]);
            return $this->dataResponse('success', 200, 'Cache Set');
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
