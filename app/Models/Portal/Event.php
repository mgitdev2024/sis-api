<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;

class Event extends Model
{
    use HasFactory;
    protected $table = 'events';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'title',
        'description',
        'location',
        'date',
        'start_time',
        'end_time',
        'is_all_day'
    ];
    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id', 'id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id', 'id');
    }
}
