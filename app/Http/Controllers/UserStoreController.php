<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;

class UserStoreController extends Controller
{
    use ResponseTrait;

    /**
     * Get user access information by employee_id
     *
     * @param string $employee_id
     */
    public function getStoreInfo(string $employee_id)
    {
        try {
            $user = User::where('employee_id', $employee_id)->first();

            if (!$user) {
                return $this->dataResponse('error', 404, 'User not found');
            }

            $responseData = [
                'employee_id' => $user->employee_id,
                'user_access' => $user->user_access ?? []
            ];

            return $this->dataResponse('success', 200, 'User access information retrieved successfully', $responseData);
        } catch (\Exception $e) {
            return $this->dataResponse('error', 500, 'Failed to retrieve user access information', $e->getMessage());
        }
    }

    /**
     * Update user access information
     *
     * @param Request $request
     */
    public function updateStoreInfo(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|string',
                'user_access' => 'required|string' // {"store_code":"C018","company_code":"MGFI"}
            ]);

            $user = User::where('employee_id', $request->employee_id)->first();

            if (!$user) {
                return $this->dataResponse('error', 404, 'User not found');
            }

            $user->update([
                'user_access' => $request->user_access
            ]);

            $responseData = [
                'employee_id' => $user->employee_id,
                'user_access' => $user->user_access
            ];

            return $this->dataResponse('success', 200, 'User access information updated successfully', $responseData);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->dataResponse('error', 422, 'Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->dataResponse('error', 500, 'Failed to update user access information', $e->getMessage());
        }
    }

    /**
     * Remove/clear user access information
     *
     * @param Request $request
     */
    public function removeStoreInfo(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|string'
            ]);

            $user = User::where('employee_id', $request->employee_id)->first();

            if (!$user) {
                return $this->dataResponse('error', 404, 'User not found');
            }

            $user->update([
                'user_access' => null
            ]);

            $responseData = [
                'status' => 'success',
                'employee_id' => $user->employee_id,
                'user_access' => null
            ];

            return $this->dataResponse('success', 200, 'User access information removed successfully', $responseData);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->dataResponse('error', 422, 'Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->dataResponse('error', 500, 'Failed to remove user access information', $e->getMessage());
        }
    }
}