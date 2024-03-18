<?php

namespace App\Models\SystemConfiguration;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModulePermission extends Model
{
    use HasFactory;

    protected $table = 'module_permissions';
    protected $fillable = [
        'permission_name',
        'status',
        'created_by_id',
        'updated_by_id'
    ];

    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id');
    }
}
