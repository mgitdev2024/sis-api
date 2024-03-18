<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;


class StoreManagemenController extends Controller
{
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'short_name' => 'nullable|string',
            'long_name' => 'required|string',
            'store_code' => 'required|string',
            'store_type' => 'required|integer',
            'store_branch' => 'required|integer',
            'store_area' => 'required|integer',
            'store_status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(Store::class, $request, $this->getRules(), 'Store');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Store::class, $request, $this->getRules(), 'Store', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['long_name', 'short_name', 'store_code'];
        return $this->readPaginatedRecord(Store::class, $request, $searchableFields, 'Store');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Store::class, $id, 'Store');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Store::class, $id, 'Store');
    }
    /*  public function readPaginatedRecord($model, $request, $searchableFields, $modelName)
     {
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
             $query = $model::orderBy('id')
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
             $reconstructedList = [];
             foreach ($dataList as $key => $value) {
                 $data = $model::findOrFail($value->id);
                 $response = $data->toArray();
                 $response['created_by'] = $data->createdBy->first_name . ' ' . $data->createdBy->middle_name . ' ' . $data->createdBy->last_name;
                 if (isset($data->updated_by_id)) {
                     $response['updated_by'] = $data->updatedBy->first_name . ' ' . $data->updatedBy->middle_name . ' ' . $data->updatedBy->last_name;
                 }
                 $reconstructedList[] = $response;
             }
             $response = [
                 'total_page' => $totalPage,
                 'data' => $reconstructedList,
             ];
             if ($dataList->isNotEmpty()) {
                 return $this->dataResponse('success', 200, __('msg.record_found'), $response);
             }
             return $this->dataResponse('error', 404, $modelName . ' ' . __('msg.record_not_found'));
         } catch (\Exception $exception) {
             return $this->dataResponse('error', 400, $exception->getMessage());
         }
     } */
}
