<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubModulePermissionModel extends Model
{
    use HasFactory;
    protected $table = 'access_submodule_permissions';
    protected $fillable = [
        'module_permission_id',
        'name',
        'code',
        'description',
        'is_enabled',
        'allow_view',
        'allow_create',
        'allow_update',
        'allow_delete',
        'allow_reopen',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function modulePermission()
    {
        return $this->belongsTo(ModulePermissionModel::class, 'module_permission_id');
    }
}
