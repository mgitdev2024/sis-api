<?php

namespace App\Traits\WMS;

use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use DB;

trait QueueSubLocationTrait
{
    use ResponseTrait;

    public function onQueueStorage($createdById, $scannedItems, $subLocationId, $isPermanent)
    {
        try {
            $data = null;
            DB::beginTransaction();
            if ($isPermanent) {

            } else {
                $data = $this->onQueueTemporaryStorage($createdById, $scannedItems, $subLocationId);
            }
            if ($data) {
                DB::commit();
                return $this->dataResponse('success', 201, 'Queue Storage ' . __('msg.create_success'), $data);
            }
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Queue Storage ' . __('msg.create_failed'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception);
        }
    }

    public function onQueueTemporaryStorage($createdById, $scannedItem, $subLocationId)
    {
        try {
            $data = null;
            $subLocation = SubLocationModel::find($subLocationId);
            $layers = json_decode($subLocation->layers, true);
            dd($layers);

            foreach ($scannedItem as $value) {
                $queueTemporaryStorage = new QueuedTemporaryStorageModel();
                $queueTemporaryStorage->sub_location_id = $value['sub_location_id'];
            }
            dd('sd');
            return $data;
        } catch (Exception $exception) {
            dd($exception);
            throw new Exception($exception->getMessage());
        }
    }
}
