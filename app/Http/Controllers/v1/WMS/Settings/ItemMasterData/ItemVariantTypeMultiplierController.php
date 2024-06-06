<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierModel;
use App\Traits\MosCrudOperationsTrait;
use Illuminate\Http\Request;

class ItemVariantTypeMultiplierController extends Controller
{
    use MosCrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'item_variant_type_id' => 'required|integer',
            'multiplier' => 'required|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemVariantTypeMultiplierModel::class, $request, $this->getRules(), 'Item Variant Type Multiplier');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemVariantTypeMultiplierModel::class, $request, $this->getRules($id), 'Item Variant Type Multiplier', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['item_variant_type_id'];
        return $this->readPaginatedRecord(ItemVariantTypeMultiplierModel::class, $request, $searchableFields, 'Item Variant Type Multiplier');
    }
    public function onGetall()
    {
        $withField = 'variantType';
        return $this->readRecord(ItemVariantTypeMultiplierModel::class, 'Item Variant Type Multiplier', $withField);
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemVariantTypeMultiplierModel::class, $id, 'Item Variant Type Multiplier');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemVariantTypeMultiplierModel::class, $id, 'Item Variant Type Multiplier');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemVariantTypeMultiplierModel::class, $id, 'Item Variant Type Multiplier', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemVariantTypeMultiplierModel::class, 'Item Variant Type Multiplier', $fields);
    }
}
