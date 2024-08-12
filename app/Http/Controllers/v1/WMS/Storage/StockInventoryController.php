<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Models\WMS\Storage\StockInventoryModel;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class StockInventoryController extends Controller
{
    use WmsCrudOperationsTrait;
    public function onGetByItemCode($item_code)
    {
        $whereFields = [
            'field' => 'item_code',
            'value' => $item_code,
        ];
        return $this->readRecordByColumnName(StockInventoryModel::class, $whereFields, 'Stock Inventory');
    }

    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required',
            'is_overwrite' => 'required|boolean', // 0 , 1
            'is_add_quantity' => 'required|boolean', // 0 , 1
        ]);
        try {
            DB::beginTransaction();
            $bulkUploadData = json_decode($request['bulk_data'], true);
            $createdById = $request['created_by_id'];

            $dataToBeOverwritten = [];
            foreach ($bulkUploadData as $data) {
                $itemCodeExist = StockInventoryModel::where('item_code', $data['item_code'])->exists();
                if ($itemCodeExist) {
                    if ($fields['is_overwrite']) {
                        if ($fields['is_add_quantity']) {
                            $stockInventory = StockInventoryModel::where('item_code', $data['item_code'])->first();
                            $stockInventory->stock_count = ($stockInventory->stock_count + $data['stock_count']);
                            $stockInventory->save();
                        } else {
                            $stockInventory = StockInventoryModel::where('item_code', $data['item_code'])->first();
                            $stockInventory->stock_count = $data['stock_count'];
                            $stockInventory->save();
                        }
                    } else {
                        $dataToBeOverwritten[] = $data['item_code'];
                        continue;
                    }
                } else {
                    $record = new StockInventoryModel();
                    $record->fill($data);
                    $record->created_by_id = $createdById;
                    $record->save();
                }

            }
            DB::commit();
            return $this->dataResponse('success', 201, 'Stock Inventory ' . __('msg.create_success'), $dataToBeOverwritten);
        } catch (Exception $exception) {
            DB::rollback();
            if ($exception instanceof \Illuminate\Database\QueryException && $exception->errorInfo[1] == 1364) {
                preg_match("/Field '(.*?)' doesn't have a default value/", $exception->getMessage(), $matches);
                return $this->dataResponse('error', 400, __('Field ":field" requires a default value.', ['field' => $matches[1] ?? 'unknown field']));
            }
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onUpdate(Request $request, $id)
    {
        $rules = [
            'stock_count' => 'required'
        ];
        return $this->updateRecordById(StockInventoryModel::class, $request, $rules, 'Stock Inventory', $id);
    }
}
