<?php

namespace App\Http\Controllers\v1\QualityAssurance;

use App\Http\Controllers\Controller;
use App\Models\QualityAssurance\ItemDispositionModel;
use App\Models\QualityAssurance\ItemDispositionRepositoryModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Http\Request;
use Exception;
use DB;
use App\Traits\MOS\MosCrudOperationsTrait;
class ItemDispositionRepositoryController extends Controller
{
    use MosCrudOperationsTrait;
    public function onGet($type, $status, $filter = null)
    {
        try {
            $whereFields = [
                'idm.type' => $type,
                'idm.status' => $status
            ];
            $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);

            if (($whereObject && $whereObject->format('Y-m-d') === $filter)) {
                $whereFields['idm.created_at'] = $filter;
            } else if ($filter == null && $status == 0) {
                $today = new \DateTime('today');
                $yesterday = new \DateTime('yesterday');
                $whereFields['idm.created_at'] = [$yesterday->format('Y-m-d 00:00:00'), $today->format('Y-m-d 23:59:59')];
                $whereFields['idm.status'] = [$status];
            }

            $itemDispositionRepositoryModel = ItemDispositionRepositoryModel::from('qa_item_disposition_repositories as idm')
                ->select([
                    'idm.id',
                    'idm.type',
                    'itm.item_code',
                    DB::raw("CONCAT(pb.batch_code, '-', LPAD(idm.item_key, 3, '0')) as sticker_no"),
                    'idm.quantity',
                    'idm.created_at',
                ])
                ->leftJoin('wms_item_masterdata as itm', 'itm.id', '=', 'idm.item_id')
                ->leftJoin('mos_production_batches as pb', 'pb.id', '=', 'idm.production_batch_id');
            foreach ($whereFields as $key => $value) {
                if ($key == 'idm.created_at' && $filter != null) {
                    $itemDispositionRepositoryModel->whereDate($key, $value);
                } else if ($key == 'idm.created_at' && $status == 0) {
                    $itemDispositionRepositoryModel->whereBetween($key, $value);
                } else {
                    $itemDispositionRepositoryModel->where($key, $value);
                }
            }
            $itemDispositionRepositoryModel = $itemDispositionRepositoryModel->orderBy('idm.id', 'DESC')->get();
            return $this->dataResponse('success', 200, 'Item Disposition Repository', $itemDispositionRepositoryModel);
        } catch (Exception $exception) {
            return $this->dataResponse('success', 200, 'Item Disposition Repository', $itemDispositionRepositoryModel);
        }
    }

    public function onGetDashboardReport($status)
    {
        try {
            // $today = new \DateTime('today');
            $forItemDispositionCount = ItemDispositionModel::select(
                DB::raw('SUM(CASE WHEN production_status = 1 AND type = 0 AND action IS NULL THEN 1 ELSE 0 END) as for_investigation_count'),
                DB::raw('SUM(CASE WHEN production_status = 1 AND type = 1 AND action IS NULL THEN 1 ELSE 0 END) as for_sampling_count'),
            )
                ->where('status', $status)
                ->first();

            $forItemDispositionRepoCount = ItemDispositionRepositoryModel::select(
                DB::raw('SUM(CASE WHEN type = 0 THEN 1 ELSE 0 END) as for_disposal_count'),
                DB::raw('SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as for_consumption_count'),
                DB::raw('SUM(CASE WHEN type = 2 THEN 1 ELSE 0 END) as for_endorsement_count'),

            )
                ->where('status', $status)
                // ->whereDate('created_at', $today->format('Y-m-d'))
                ->first();
            $data = [
                'for_investigation_count' => $forItemDispositionCount->for_investigation_count ?? 0,
                'for_sampling_count' => $forItemDispositionCount->for_sampling_count ?? 0,
                'for_disposal_count' => $forItemDispositionRepoCount->for_disposal_count ?? 0,
                'for_consumption_count' => $forItemDispositionRepoCount->for_consumption_count ?? 0,
                'for_endorsement_count' => $forItemDispositionRepoCount->for_endorsement_count ?? 0,
            ];

            return $this->dataResponse('success', 200, 'Item Disposition Repository', $data);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'), $exception);
        }
    }

    public function onChangeStatus(Request $request)
    {
        try {
            $fields = $request->validate([
                'item_repository_ids' => 'required|string',
                'updated_by_id' => 'required|string',
            ]);
            $itemRepositoryIds = json_decode($fields['item_repository_ids'], true);
            if (count($itemRepositoryIds) > 0) {
                foreach ($itemRepositoryIds as $value) {
                    $itemRepositoryModel = ItemDispositionRepositoryModel::find($value);
                    $itemRepositoryModel->status = 0;
                    $itemRepositoryModel->updated_by_id = $fields['updated_by_id'];
                    $itemRepositoryModel->save();
                }
                return $this->dataResponse('success', 200, 'Item Disposition Repository ' . __('msg.update_success'));
            }
            return $this->dataResponse('error', 400, __('msg.record_not_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'), $exception);
        }
    }
}
