<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\Event;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class EventController extends Controller
{
    use CrudOperationsTrait;
    public Event $event;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'location' => 'required|string',
            'date' => 'required|date',
            'start_time' => 'required_if:is_all_day,0|nullable',
            'end_time' => 'required_if:is_all_day,0|nullable',
            'is_all_day' => 'nullable|integer',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(Event::class, $request, $this->getRules(), 'Event');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(Event::class, $request, $this->getRules(), 'Event', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['title'];
        return $this->readPaginatedRecord(Event::class, $request, $searchableFields, 'Event');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(Event::class, $id, 'Event');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(Event::class, $id, 'Event');
    }
}
