<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Traits\Report\GeneratedReportDataTrait;
use Illuminate\Http\Request;

class GeneratedReportDataController extends Controller
{
    use GeneratedReportDataTrait;
    public function onGet($model_name)
    {
        return $this->readRecord($model_name);
    }

    public function onGetById($id)
    {
        return $this->readRecordById($id);
    }

    public function onGetByFilter(Request $request)
    {
        return $this->readRecordByFilter($request);
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById($id);
    }
}
