<?php

namespace App\Traits\Report;

use App\Models\Report\GeneratedReportDataModel;
use Exception;
use App\Traits\ResponseTrait;
use DB;

trait GeneratedReportDataTrait
{
    use ResponseTrait;

    public function initializeRecord($uuid, $model, $createdById, $transactionDate)
    {
        try {
            $generatedReportData = GeneratedReportDataModel::where([
                'transaction_date' => $transactionDate,
                'model_name' => $model
                ])->first();
            if ($generatedReportData) {
                $generatedReportData->uuid = $uuid;
                $generatedReportData->status = 0;
            }
            GeneratedReportDataModel::create([
                'uuid' => $uuid,
                'model_name' => $model,
                'created_by_id' => $createdById,
                'status' => 0,
            ]);

        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function fillReportData($uuid, $data, $date, $storeCode = null, $subUnit = null)
    {
        try {

            $generatedReportData = GeneratedReportDataModel::where('uuid', $uuid)->first();

            if ($generatedReportData) {
                $generatedReportData->store_code = $storeCode;
                $generatedReportData->store_sub_unit_short_name = $subUnit;
                $generatedReportData->report_data = json_encode($data);
                $generatedReportData->date_range = $date;
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
                'transaction_date' => $transactionDate,
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
            GeneratedReportDataModel::where('id', $id)->delete();

        } catch (Exception $exception) {
            throw $exception;
        }
    }
}
