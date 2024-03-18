<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\Announcement;
use Illuminate\Http\Request;
use Exception;

use App\Traits\CrudOperationsTrait;

class AnnouncementController extends Controller
{
    use CrudOperationsTrait;
    public Announcement $announcement;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'cover' => 'string|nullable',
            'file' => 'string|nullable',
            'title' => 'required|string',
            'description' => 'required|string',
            'from' => 'required|string',
            'to' => 'required|string',
            'is_allow_comment' => 'nullable|integer',
            'status' => 'nullable|integer',
            'type' => 'required|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        $request['type'] == 1 ? 'Announcement' : 'Feeds';

        return $this->createRecord(Announcement::class, $request, $this->getRules(), $request['type']);
    }
    public function onUpdateById(Request $request, $id)
    {
        $request['type'] == 1 ? 'Announcement' : 'Feeds';
        return $this->updateRecordById(Announcement::class, $request, $this->getRules(), $request['type'], $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['title', 'description', 'type'];
        return $this->readPaginatedRecord(Announcement::class, $request, $searchableFields, $request['type']);
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Announcement::class, $id, 'Feeds');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Announcement::class, $id, 'Feeds');
    }
    public function onReconstructResponse($id)
    {
        $data = Announcement::findOrFail($id);
        $response = $data->toArray(); // Get all attributes as an array
        $response['created_by'] = $data->createdBy->first_name . ' ' . $data->createdBy->middle_name . ' ' . $data->createdBy->last_name;
        $response['created_by_image'] = $data->createdByEmployment->id_picture ?? '';
        $response['created_by_position'] = $data->createdByEmployment->position_id ?? '';
        if (isset($data->updated_by_id)) {
            $response['updated_by'] = $data->updatedBy->first_name . ' ' . $data->updatedBy->middle_name . ' ' . $data->updatedBy->last_name;
            $response['updated_by_image'] = $data->updatedByEmployment->id_picture;
            $response['updated_by_position'] = $data->updatedByEmployment->position_id;
        }
        return $response;
    }
}
