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
            'item_key' => 'nullable',
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
            return $this->dataResponse('error', 400, 'Production Historical Log ' . __('msg.create_failed'));
        }
    }
    public function onGetCurrent(Request $request)
    {
        $fields = $request->validate([
            'entity_id' => 'nullable',
            'entity_model' => 'nullable',
            'is_item_key' => 'nullable|boolean',
            'item_key' => 'nullable',
        ]);

        $whereFields = [];
        if (isset($fields['entity_id'])) {
            $whereFields = [
                'entity_id' => $fields['entity_id'],
                'entity_model' => $fields['entity_model'],
            ];

            if (isset($fields['is_item_key'])) {
                $whereFields = [
                    'item_key' => $fields['item_key']
                ];
            }
        }

        $notNullFields = null;
        if (isset($fields['is_item_key'])) {
            $notNullFields = [
                'item_key'
            ];
        }


        return $this->readCurrentRecord(ProductionHistoricalLogModel::class, null, $whereFields, null, null, 'Production History Log', false, $notNullFields);
    }
}
