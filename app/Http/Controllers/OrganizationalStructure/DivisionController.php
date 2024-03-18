<?php

namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\Division;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;
use Exception;

class DivisionController extends Controller
{
    use CrudOperationsTrait;
    use ResponseTrait;

    public Division $division;
    public static function getRules()
    {
        $rules = [
            'created_by_id' => 'required|exists:personal_informations,id',
            'division_code' => 'nullable|string|max:10',
            'division_short_name' => 'nullable|string|max:50',
            'division_long_name' => 'nullable|string|max:50',
            'status' => 'nullable|integer',
        ];
        return $rules;
    }
    public function onCreate(Request $request)
    {
        if (!$request['division_short_name']) {
            $generateShortName = $this->onGenerateShortName($request['division_long_name']);
            $request['division_short_name'] = $generateShortName;
        }

        return $this->createRecord(Division::class, $request, $this->getRules(), 'Division');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Division::class, $request, $this->getRules(), 'Division', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['division_code', 'division_short_name', 'division_long_name'];
        return $this->readPaginatedRecord(Division::class, $request, $searchableFields, 'Division');
    }
    public function onGetAll()
    {
        return $this->readRecord(Division::class, 'Division');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Division::class, $id, 'Division');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Division::class, $id, 'Division');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(Division::class, $id, 'Division');
    }
    public function onGenerateShortName($data)
    {
        $explodeData = explode(' ', $data);
        $shortName = '';
        if (count($explodeData) > 1) {
            foreach ($explodeData as $title) {
                $shortName .= strtoupper($title[0]);
            }
        } else {
            for ($ctr = 0; $ctr <= strlen($data) - 1; $ctr++) {
                $shortName .= strtoupper($data[$ctr]);
                if (strlen($shortName) >= 3) {
                    break;
                }
            }
        }
        return $shortName;
    }
    public function onBulkUpload(Request $request)
    {
        try {
            DB::beginTransaction();
            $file = $request['file'];
            $created_by_id = $request['created_by_id'];
            $currentTime = now();
            $dataToInsert = json_decode($file, true);
            foreach ($dataToInsert as $row) {
                if (!$row['division_short_name']) {
                    $row['division_short_name'] = $this->onGenerateShortName($row['division_long_name']);
                }
                $listData[] = [
                    'created_by_id' => $created_by_id,
                    'division_code' => $row['division_code'],
                    'division_short_name' => $row['division_short_name'],
                    'division_long_name' => $row['division_long_name'],
                    'status' => 1,
                    'created_at' => $currentTime,
                ];
            }
            Division::insert($listData);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.bulk_upload_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
