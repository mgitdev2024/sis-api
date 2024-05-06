<?php

namespace App\Http\Controllers\v1\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Settings\WarehouseLocationModel;
use App\Models\Warehouse\WarehouseReceivingModel;
use Illuminate\Http\Request;

use App\Traits\CrudOperationsTrait;

class WarehouseReceivingController extends Controller
{
    use CrudOperationsTrait;
    public function onGetCurrent($status)
    {
        $whereFields = [
            'status' => $status // 0, 1
        ];

        $orderFields = [
            'reference_number' => 'ASC'
        ];
        return $this->readCurrentRecord(WarehouseReceivingModel::class, null, $whereFields, null, $orderFields, 'Warehouse Receiving');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WarehouseReceivingModel::class, $id, 'Warehouse Receiving');
    }
}
