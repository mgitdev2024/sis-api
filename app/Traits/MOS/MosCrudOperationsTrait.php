<?php

namespace App\Traits\MOS;

use App\Http\Controllers\v1\History\ProductionLogController;
use Exception;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Database\QueryException;

trait MosCrudOperationsTrait
{
    use ResponseTrait, ProductionLogTrait;
    public function createRecord($model, $request, $rules, $modelName, $path = null)
    {
        $fields = $request->validate($rules);
        try {
            $record = new $model();
            $record->fill($fields);
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store($path);
                $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
                $record->attachment = $filepath;
            }
            $record->save();
            $this->createProductionLog($model, $record->id, $fields, $fields['created_by_id'], 0);
            return $this->dataResponse('success', 201, $modelName . ' ' . __('msg.create_success'), $record);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }
    public function updateRecordById($model, $request, $rules, $modelName, $id, $path = null)
    {
        $fields = $request->validate($rules);
        try {
            $record = new $model();
            $record = $model::find($id);
            if ($record) {
                $record->update($fields);
                if ($request->hasFile('attachment')) {
                    $attachmentPath = $request->file('attachment')->store($path);
                    $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
                    $record->attachment = $filepath;
                    $record->save();
                }
                $this->createProductionLog($model, $record->id, $fields, $fields['updated_by_id'], 1);
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
    public function readRecord($model, $modelName, $withField = null, $orderFields = null)
    {
        try {
            $dataList = $model::query();

            if ($withField != null) {
                $dataList = $dataList->with($withField);
            }
            if ($orderFields != null) {
                $dataList->orderBy($orderFields['key'], $orderFields['value']);

            }
            $dataList = $dataList->get();
            if ($dataList->isNotEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $dataList);
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function readRecordById($model, $id, $modelName, $withField = null, $whereFields = null)
    {
        try {
            $query = $model::query();
            $data = null;
            if ($withField != null) {
                $query = $query->with($withField);
            }

            if ($whereFields != null) {
                $data = $query->where($whereFields['key'], $whereFields['value'])->first();
            } else {
                $data = $query->find($id);
            }
            if ($data) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }

            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function readRecordByParentId($model, $modelName, $parentField, $id = null, $withField = null)
    {
        try {
            $query = $model::query();

            if ($withField) {
                $query->with($withField);
            }

            if ($id) {
                $query->where($parentField, $id);
            }
            $data = $query->get();

            if ($data->isNotEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }

            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function readCurrentRecord($model, $id, $whereFields, $withFields, $orderFields, $modelName, $triggerOr = false, $notNullFields = null)
    {
        try {
            $data = $model::query();
            if ($whereFields) {
                foreach ($whereFields as $field => $value) {
                    if (is_array($value)) {
                        if ($triggerOr) {
                            $data->orWhere(function ($query) use ($field, $value) {
                                foreach ($value as $whereValue) {
                                    $query->orWhere($field, $whereValue);
                                }
                            });
                        } else {
                            $data->where(function ($query) use ($field, $value) {
                                foreach ($value as $whereValue) {
                                    $query->orWhere($field, $whereValue);
                                }
                            });
                        }
                    } else {
                        $data->where($field, $value);
                    }
                }
            }

            if ($notNullFields) {
                if ($triggerOr) {
                    foreach ($notNullFields as $field) {
                        $data->orWhereNotNull($field);
                    }
                } else {
                    foreach ($notNullFields as $field) {
                        $data->whereNotNull($field);
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
                $this->createProductionLog($model, $model->id, $data, $fields['created_by_id'], 1);
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
        } catch (QueryException $exception) {
            if ($exception->getCode() == 23000) {
                return $this->dataResponse('error', 400, __('msg.delete_failed_fk_constraint', ['modelName' => $modelName]));
            }
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function bulkUpload($model, $modelName, $request)
    {
        try {
            DB::beginTransaction();
            $bulkUploadData = json_decode($request['bulk_data'], true);
            $createdById = $request['created_by_id'];

            foreach ($bulkUploadData as $data) {
                $record = new $model();
                $record->fill($data);
                $record->created_by_id = $createdById;
                $record->save();
            }
            DB::commit();
            return $this->dataResponse('success', 201, $modelName . ' ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollback();
            if ($exception instanceof \Illuminate\Database\QueryException && $exception->errorInfo[1] == 1364) {
                preg_match("/Field '(.*?)' doesn't have a default value/", $exception->getMessage(), $matches);
                return $this->dataResponse('error', 400, __('Field ":field" requires a default value.', ['field' => $matches[1] ?? 'unknown field']));
            }
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function readLikeRecord($model, $modelName, $columName, $name, $whereFields)
    {
        try {
            if ($name != null) {
                $data = $model::where($columName, 'like', $name . '%');
                if ($whereFields) {
                    foreach ($whereFields as $field => $value) {
                        $data->where($field, $value['operator'], $value['value']);
                    }
                }
                $data = $data->get();
                if ($data->isNotEmpty()) {
                    return $this->dataResponse('success', 200, __('msg.record_found'), $data);
                }
            }
            return $this->dataResponse('error', 200, $modelName . ' ' . __('msg.record_not_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}

