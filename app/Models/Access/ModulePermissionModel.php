<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModulePermissionModel extends Model
{
    use HasFactory;
    protected $table = 'module_permissions';
    protected $fillable = [
        'scm_system_id',
        'name',
        'code',
        'description',
        'is_enabled',
        'allow_view',
        'allow_create',
        'allow_update',
        'allow_delete',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function scmSystem()
    {
        return $this->belongsTo(ScmSystemModel::class);
    }

    public function subModulePermissions()
    {
        return $this->hasMany(SubModulePermissionModel::class, 'module_permission_id');
    }
}
