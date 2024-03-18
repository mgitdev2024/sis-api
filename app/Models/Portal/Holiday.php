<?php

namespace App\Models\Portal;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;

class Holiday extends Model
{
    use HasFactory;
    protected $table = 'holidays';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'title',
        'description',
        'location',
        'is_special',
        'date',
        'is_local',
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