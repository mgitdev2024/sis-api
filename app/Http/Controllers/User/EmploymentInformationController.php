<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EmploymentInformation;
use App\Traits\CrudOperationsTrait;


class EmploymentInformationController extends Controller
{
    use CrudOperationsTrait;

    public function onGetDataById($personal_id)
    {
        try {
            $personalInformation = EmploymentInformation::find($personal_id);

            if ($personalInformation) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $personalInformation);
            }
            return $this->dataResponse('error', 404, 'Employee`s' . ' ' . __('msg.record_not_found'));
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
