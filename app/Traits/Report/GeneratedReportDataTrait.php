<?php

namespace App\Traits\Report;

use App\Models\Report\GeneratedReportDataModel;
use Exception;
use App\Traits\ResponseTrait;
use DB;

trait GeneratedReportDataTrait
{
    use ResponseTrait;

    public function initializeRecord($uuid, $model, $createdById, $transactionDate, $storeCode = null, $subUnit = null)
    {
        try {
            $generatedReportData = GeneratedReportDataModel::where([
                'date_range' => $transactionDate,
                'model_name' => $model
                ])->first();

            if ($generatedReportData) {
                $generatedReportData->update([
                    'uuid' => $uuid,
                    'updated_at' => now(),
                    'status' => 0,
                ]);
            }else {
                GeneratedReportDataModel::create([
                    'uuid' => $uuid,
                    'model_name' => $model,
                    'created_by_id' => $createdById,
                    'date_range' => $transactionDate,
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $subUnit,
                    'status' => 0,
                ]);
            }
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function fillReportData($uuid, $data)
    {

        try {

            $generatedReportData = GeneratedReportDataModel::where('uuid', $uuid)->first();

            if ($generatedReportData) {
                $generatedReportData->report_data = json_encode(array_values($data));
                $generatedReportData->status = 1;
                $generatedReportData->save();
                return;
            }

        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function readRecord($model)
    {
        try {
            $record = GeneratedReportDataModel::select([
                'id',
                'store_code',
                'store_sub_unit_short_name',
                'model_name',
                'status',
                'date_range',
                'created_at'
            ])
                ->where('model_name', $model)
                ->orderBy('id', 'desc')
                ->get();

            return $this->dataResponse('success', 200, __('msg.record_found'), $record);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function readRecordById($id)
    {
        try {
            $record = GeneratedReportDataModel::find($id);

            return $this->dataResponse('success', 200, __('msg.record_found'), $record);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function readRecordByTransactionDate($modelName, $transactionDate)
    {
        try {
            $record = GeneratedReportDataModel::where([
                'date_range' => $transactionDate,
                'model_name' => $modelName
            ])->get();

            return $this->dataResponse('success', 200, __('msg.record_found'), $record);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function deleteRecordById($id)
    {
        try {
            $record = GeneratedReportDataModel::where('id', $id)->delete();
            if ($record) {
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }
            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        } catch (Exception $exception) {
            throw $exception;
        }
    }
}
