<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProducedItemModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ProducedItemController extends Controller
{
    use CrudOperationsTrait;
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'expiration_date' => 'required|date',
        ];
        return $this->updateRecordById(ProducedItemModel::class, $request, $rules, 'Produced Item', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProducedItemModel::class, $request, $searchableFields, 'Produced Item');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProducedItemModel::class, $id, 'Produced Item');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProducedItemModel::class, $id, 'Produced Item');
    }
}
