<?php

namespace App\Http\Controllers\v1\QualityAssurance;

use App\Http\Controllers\Controller;
use App\Models\QualityAssurance\ItemDispositionRepositoryModel;
use Illuminate\Http\Request;
use Exception;
use DB;
use App\Traits\MOS\MosCrudOperationsTrait;
class ItemDispositionRepositoryController extends Controller
{
    use MosCrudOperationsTrait;
    public function onGet($type, $status, $filter = null)
    {
        $whereFields = [
            'type' => $type,
            'status' => $status
        ];
        $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
        if (($whereObject && $whereObject->format('Y-m-d') === $filter) && $status == 0) {
            $whereFields['created_at'] = $filter;
        } else if ($status == 0) {
            $today = new \DateTime('today');
            $yesterday = new \DateTime('yesterday');
            $whereFields['created_at'] = [$yesterday->format('Y-m-d'), $today->format('Y-m-d')];
            $whereFields['status'] = [0];
        }


        $orderFields = [
            "created_at" => "DESC",
        ];
        return $this->readCurrentRecord(ItemDispositionRepositoryModel::class, $filter, $whereFields, null, $orderFields, 'Item Disposition Repository', true);
    }
}
