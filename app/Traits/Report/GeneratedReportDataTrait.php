<?php

namespace App\Traits\Report;

use App\Models\Report\GeneratedReportDataModel;
use Exception;
use App\Traits\ResponseTrait;
use DB;

trait GeneratedReportDataTrait
{
    use ResponseTrait;

    public function initializeRecord($uuid, $model, $createdById)
    {
        try {
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

    public function fillReportData($uuid, $data, $date, $store_code = null, $sub_unit = null)
    {
        try {

            $generatedReportData = GeneratedReportDataModel::where('uuid', $uuid)->first();

            if ($generatedReportData) {
                $generatedReportData->store_code = $store_code;
                $generatedReportData->store_sub_unit_short_name = $sub_unit;
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

    public function readRecord($model, $createdById)
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
                ->where('created_by_id', $createdById)
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

    public function deleteRecord($id)
    {
        try {
            GeneratedReportDataModel::where('id', $id)->delete();

        } catch (Exception $exception) {
            throw $exception;
        }
    }
}
