<?php

namespace App\Http\Controllers\v1\History;

use App\Http\Controllers\Controller;
use App\Models\History\ProductionLogModel;
use App\Traits\MosCrudOperationsTrait;
use Illuminate\Http\Request;

class ProductionLogController extends Controller
{
    use MosCrudOperationsTrait;
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
            $record = new ProductionLogModel();
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

        $query = \DB::table('mos_production_logs');

        if (isset($fields['entity_id'])) {
            $query->where('entity_id', $fields['entity_id'])
                ->where('entity_model', $fields['entity_model']);
        }
        if (isset($fields['item_key'])) {
            $query->where('item_key', $fields['item_key']);
        }
        if (isset($fields['is_item_key']) && !$fields['is_item_key']) {
            $query->whereNull('item_key');
        } else if (isset($fields['is_item_key']) && $fields['is_item_key']) {
            $query->whereNotNull('item_key');
        }

        $results = $query->get();
        return $this->dataResponse('success', 201, 'Production Historical Log ' . __('msg.create_success'), $results);
    }
}
