<?php

namespace App\Http\Controllers\v1\History;

use App\Http\Controllers\Controller;
use App\Models\History\ProductionHistoricalLogModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class ProductionHistoricalLogController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required',
            'entity_model' => 'required',
            'entity_id' => 'required',
            'data' => 'required',
            'action' => 'required|in:0,1,2',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionHistoricalLogModel::class, $request, $this->getRules(), 'Production Historical Log');
    }
}
