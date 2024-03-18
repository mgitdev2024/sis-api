<?php

namespace App\Models\OrganizationalStructure;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;
    protected $table = 'divisions';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'division_code',
        'division_short_name',
        'division_long_name',
        'status',
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
