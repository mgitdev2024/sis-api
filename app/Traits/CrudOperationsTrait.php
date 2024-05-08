<?php

namespace App\Traits;

use App\Http\Controllers\v1\History\ProductionHistoricalLogController;
use Exception;
use App\Traits\ResponseTrait;

trait CrudOperationsTrait
{
    use ResponseTrait, ProductionHistoricalLogTrait;
    public function createRecord($model, $request, $rules, $modelName)
    {
        $fields = $request->validate($rules);
        try {
            $record = new $model();
            $record->fill($fields);
            $record->save();
            $this->createProductionHistoricalLog($model, $record->id, $fields, $fields['created_by_id'], 0);
            return $this->dataResponse('success', 201, $modelName . ' ' . __('msg.create_success'), $record);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }
    public function updateRecordById($model, $request, $rules, $modelName, $id)
    {
        $fields = $request->validate($rules);
        try {
            $record = new $model();
            $record = $model::find($id);
            if ($record) {
                $record->update($fields);
                $this->createProductionHistoricalLog($model, $record->id, $fields, $fields['created_by_id'], 1);
                return $this->dataResponse('success', 201, $modelName . ' ' . __('msg.update_success'), $record);
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.update_failed'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function readPaginatedRecord($model, $request, $searchableFields, $modelName)
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
            // $dataList = $query->limit($display)->offset($offset)->get();
            $dataList = $query->get();
            $totalPage = max(ceil($query->count() / $display), 1);
            $reconstructedList = [];
            /*  foreach ($dataList as $key => $value) {
                 $data = $model::findOrFail($value->id);
                 $response = $data->toArray();
                 $response['created_by_id'] = $data->createdBy->first_name . ' ' . $data->createdBy->middle_name . ' ' . $data->createdBy->last_name;
                 if (isset($data->updated_by_id)) {
                     $response['updated_by_id'] = $data->updatedBy->first_name . ' ' . $data->updatedBy->middle_name . ' ' . $data->updatedBy->last_name;
                 }
                 $reconstructedList[] = $response;
             } */
            $response = [
                'total_page' => $totalPage,
                'data' => $dataList,
            ];
            if ($dataList->isNotEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $response);
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function readRecord($model, $modelName)
    {
        try {
            $dataList = $model::get();
            if ($dataList->isNotEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $dataList);
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function readRecordById($model, $id, $modelName)
    {
        try {
            $data = $model::find($id);
            if ($data) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function readCurrentRecord($model, $id, $whereFields, $withFields, $orderFields, $modelName)
    {
        try {
            $data = $model::orderBy('id', 'ASC');
            if ($whereFields) {
                foreach ($whereFields as $field => $value) {
                    if (is_array($value)) {
                        $data->where(function ($query) use ($field, $value) {
                            foreach ($value as $whereValue) {
                                $query->orWhere($field, $whereValue);
                            }
                        });
                    } else {
                        $data->where($field, $value);
                    }
                }
            }

            if ($orderFields) {
                foreach ($orderFields as $field => $value) {
                    $data->orderBy($field, $value);
                }
            }
            if ($withFields != null) {
                $data->with($withFields);
            }
            $dataList = $data->get();
            if ($dataList->isNotEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $dataList);
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function changeStatusRecordById($model, $id, $modelName, $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            $data = $model::find($id);
            if ($data) {
                $response = $data->toArray();
                $response['status'] = !$response['status'];
                $data->update($response);
                $this->createProductionHistoricalLog($model, $model->id, $data, $fields['created_by_id'], 1);
                return $this->dataResponse('success', 200, __('msg.update_success'), $response);
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function deleteRecordById($model, $id, $modelName)
    {
        try {
            $deletedRows = $model::destroy($id);
            if ($deletedRows) {
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.delete_failed'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}

