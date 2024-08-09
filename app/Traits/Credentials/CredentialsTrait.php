<?php

namespace App\Traits\Credentials;

use Exception;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

trait CredentialsTrait
{
    use ResponseTrait;

    public function onGetName($employeeId, $token)
    {
        $apiUrl = env('API_URL');
        $url = "{$apiUrl}/user/get/employee_id/{$employeeId}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json()['success']['data'];

                // Extract data
                $firstName = $data['first_name'] ?? '';
                $lastName = $data['last_name'] ?? '';
                $middleName = $data['middle_name'] ?? '';
                $prefix = $data['prefix'] ?? '';
                $suffix = $data['suffix'] ?? '';

                // Build full name
                $fullName = trim(
                    implode(' ', array_filter([$prefix, $firstName, $middleName, $lastName, $suffix]))
                );

                return $fullName;
            }

            // Return the employee ID if the request was not successful
            return $employeeId;
        } catch (Exception $e) {
            report($e); // Log the exception
            throw new Exception('An error occurred while fetching the data.', 500, $e);
        }
    }
}

