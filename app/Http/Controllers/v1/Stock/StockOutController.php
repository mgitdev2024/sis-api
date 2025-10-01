<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockOutModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use DB;
use Exception;
class StockOutController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;

    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'nullable',
            'attachment' => 'nullable',
            'stock_out_items' => 'required', // [{"ic":"CR 12","idc":"Cheeseroll Box of 12","icn":"BREADS","icv":"Mini","uom":"Box","q":1}]
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $referenceNumber = StockOutModel::onGenerateReferenceNumber();
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'] ?? null;
            $stockOutDate = now();
            $createdById = $fields['created_by_id'];

            $attachmentPath = null;
            if (isset($fields['attachment']) && $fields['attachment'] != null) {
                $attachmentPath = $request->file('attachment')->store('public/attachments/stock_out');
                $attachmentPath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
            }
            $stockOutModel = StockOutModel::create([
                'reference_number' => $referenceNumber,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'stock_out_date' => $stockOutDate,
                'attachment' => $attachmentPath,
                'created_by_id' => $createdById,
            ]);

            $stockOutId = $stockOutModel->id;
            $stockOutItems = json_decode($fields['stock_out_items'], true);
            $stockOutItemController = new StockOutItemController();
            $stockOutItemController->onCreateStockOutItem($stockOutItems, $stockOutId, $createdById, $referenceNumber, $storeCode, $storeSubUnitShortName);
            DB::commit();
            return $this->dataResponse('success', 201, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onGet($storeCode, $storeSubUnitShortName = null)
    {
        $orderFields = [
            'id' => 'DESC',
        ];

        $whereFields = [
            'store_code' => $storeCode,
        ];

        if ($storeSubUnitShortName) {
            $whereFields['store_sub_unit_short_name'] = $storeSubUnitShortName;
        }
        return $this->readCurrentRecord(StockOutModel::class, null, $whereFields, null, $orderFields, 'Stock Out');
    }
}
