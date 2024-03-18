<?php

namespace App\Models\SystemConfiguration;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleFunction extends Model
{
    use HasFactory;
    protected $table = 'module_functions';

    protected $fillable = [
        'function_code',
        'sub_module_id',
        'module_permission_id',
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


    public function subModule()
    {
        return $this->belongsTo(SubModule::class, 'sub_module_id');
    }

    public function modulePermission()
    {
        return $this->belongsTo(ModulePermission::class, 'module_permission_id');
    }
}
