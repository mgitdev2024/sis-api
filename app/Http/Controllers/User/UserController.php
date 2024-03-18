<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PersonalInformation;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;


class UserController extends Controller
{
    use CrudOperationsTrait;

    public function onGetDataById($personal_id)
    {
        try {
            $personalInformation = PersonalInformation::find($personal_id);

            if ($personalInformation) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $personalInformation);
            }
            return $this->dataResponse('error', 404, 'Employee`s' . ' ' . __('msg.record_not_found'));
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['first_name', 'last_name', 'employee_id'];
        try {
            $fields = $request->validate([
                'display' => 'nullable|integer',
                'page' => 'nullable|integer',
                'search' => 'nullable|string',
                'type' => 'nullable',
            ]);
            $page = $fields['page'] ?? 1;
            $display = $fields['display'] ?? 10;
            $offset = ($page - 1) * $display;
            $search = $fields['search'] ?? '';
            $query = PersonalInformation::orderBy('id')
                ->when($search, function ($query) use ($searchableFields, $search) {
                    $query->where(function ($innerQuery) use ($searchableFields, $search) {
                        foreach ($searchableFields as $field) {
                            $innerQuery->orWhere($field, 'like', '%' . $search . '%');
                        }
                    });
                });
            if (isset($fields['type'])) {
                $query->where('type', $fields['type']);
            }
            if (isset($request['is_pinned'])) {
                $query->where('is_pinned', $request['is_pinned']);
            }
            $dataList = $query->limit($display)->offset($offset)->get();
            $totalPage = max(ceil($query->count() / $display), 1);

            $response = [
                'total_page' => $totalPage,
                'data' => $dataList,
            ];
            if ($dataList->isNotEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $response);
            }
            return $this->dataResponse('error', 404, 'Employee ' . ' ' . __('msg.record_not_found'));
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
