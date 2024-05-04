<?php

namespace App\Http\Controllers\v1\Settings\Items;

use App\Http\Controllers\Controller;
use App\Models\Settings\ItemMovementModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;

class ItemMovementController extends Controller
{
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function getRules()
    {
        return [
            // |exists:personal_informations,id
            'created_by_id' => 'required',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'status' => 'nullable|integer',

        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemMovementModel::class, $request, $this->getRules(), 'Item Movement');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemMovementModel::class, $request, $this->getRules(), 'Item Movement', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name', 'description'];
        return $this->readPaginatedRecord(ItemMovementModel::class, $request, $searchableFields, 'Item Movement');
    }
    public function onGetall(Request $request)
    {
        return $this->readRecord(ItemMovementModel::class, $request, 'Item Movement');
    }
    public function onGetById($id, Request $request)
    {
        return $this->readRecordById(ItemMovementModel::class, $id, $request, 'Item Movement');
    }
    public function onDeleteById($id, Request $request)
    {
        return $this->deleteRecordById(ItemMovementModel::class, $id, $request, 'Item Movement');
    }
}
