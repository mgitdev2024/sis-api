<?php

namespace App\Http\Controllers\v1\History;

use App\Http\Controllers\Controller;
use App\Models\History\ProductionLogModel;
use App\Traits\MOS\MosCrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
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

        $productionLogsQuery = DB::table('mos_production_logs')
            ->select('id', 'entity_id', 'entity_model', 'item_key', 'data', 'action', 'created_at')
            ->when(isset($fields['entity_id']), function ($query) use ($fields) {
                return $query->where('entity_id', $fields['entity_id'])
                    ->where('entity_model', $fields['entity_model']);
            })
            ->when(isset($fields['item_key']), function ($query) use ($fields) {
                return $query->where('item_key', $fields['item_key']);
            })
            ->when(isset($fields['is_item_key']), function ($query) use ($fields) {
                if (!$fields['is_item_key']) {
                    return $query->whereNull('item_key');
                } else {
                    return $query->whereNotNull('item_key');
                }
            })->get();

        $archivedLogsQuery = DB::connection(env('LOG_DB_CONNECTION'))->table(env('LOG_DB_DATABASE') . '.archived_production_logs')
            ->select('id', 'entity_id', 'entity_model', 'item_key', 'data', 'action', 'created_at')
            ->when(isset($fields['entity_id']), function ($query) use ($fields) {
                return $query->where('entity_id', $fields['entity_id'])
                    ->where('entity_model', $fields['entity_model']);
            })
            ->when(isset($fields['item_key']), function ($query) use ($fields) {
                return $query->where('item_key', $fields['item_key']);
            })
            ->when(isset($fields['is_item_key']), function ($query) use ($fields) {
                if (!$fields['is_item_key']) {
                    return $query->whereNull('item_key');
                } else {
                    return $query->whereNotNull('item_key');
                }
            })->get();

        $combinedLogs = $productionLogsQuery->merge($archivedLogsQuery);

        return $this->dataResponse('success', 201, 'Combined Logs', $combinedLogs);
    }


}
