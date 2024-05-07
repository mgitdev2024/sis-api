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
        $fields = $request->validate($this->getRules());
        try {
            $record = new ProductionHistoricalLogModel();
            $record->fill($fields);
            $record->save();
            return $this->dataResponse('success', 201, 'Production Historical Log ' . __('msg.create_success'), $record);
        } catch (\Exception $exception) {
            dd($exception);
            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }

    }
}
