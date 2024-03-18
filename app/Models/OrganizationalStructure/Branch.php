<?php

namespace App\Models\OrganizationalStructure;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;


class Branch extends Model
{
    use HasFactory;
    protected $table = 'branches';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'branch_code',
        'branch_name',
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
