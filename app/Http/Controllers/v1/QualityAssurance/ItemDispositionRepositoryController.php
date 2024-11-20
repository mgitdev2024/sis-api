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
            'idm.type' => $type,
            'idm.status' => $status
        ];
        $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
        if (($whereObject && $whereObject->format('Y-m-d') === $filter) && $status == 0) {
            $whereFields['idm.created_at'] = $filter;
        } else if ($status == 0) {
            $today = new \DateTime('today');
            $yesterday = new \DateTime('yesterday');
            $whereFields['idm.created_at'] = [$yesterday->format('Y-m-d'), $today->format('Y-m-d')];
            $whereFields['idm.status'] = [0];
        }

        $itemDispositionRepositoryModel = ItemDispositionRepositoryModel::from('qa_item_disposition_repositories as idm')
            ->select([
                'idm.id',
                'idm.type',
                'itm.item_code',
                DB::raw("CONCAT(pb.batch_code, '-', LPAD(idm.item_key, 3, '0')) as sticker_no"),
                'idm.quantity'
            ])
            ->leftJoin('wms_item_masterdata as itm', 'itm.id', '=', 'idm.item_id')
            ->leftJoin('mos_production_batches as pb', 'pb.id', '=', 'idm.production_batch_id');
        foreach ($whereFields as $key => $value) {
            $itemDispositionRepositoryModel->where($key, $value);
        }
        $itemDispositionRepositoryModel = $itemDispositionRepositoryModel->orderBy('idm.id', 'DESC')->get();
        return $this->dataResponse('success', 200, 'Item Disposition Repository', $itemDispositionRepositoryModel);
    }
}
