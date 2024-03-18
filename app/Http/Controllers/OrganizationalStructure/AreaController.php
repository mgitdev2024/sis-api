<?php

namespace App\Http\Controllers\OrganizationalStructure;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalStructure\Area;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class AreaController extends Controller
{
    use CrudOperationsTrait;
    public Area $area;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'area_code' => 'required|string',
            'area_name' => 'required|string',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(Area::class, $request, $this->getRules(), 'Area');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Area::class, $request, $this->getRules(), 'Area', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['area_code', 'area_name'];
        return $this->readPaginatedRecord(Area::class, $request, $searchableFields, 'Area');
    }
    public function onGetAll()
    {
        return $this->readRecord(Area::class, 'Area');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Area::class, $id, 'Area');
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(Area::class, $id, 'Area');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Area::class, $id, 'Area');
    }
}
