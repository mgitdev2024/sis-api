<?php

namespace App\Models\SystemConfiguration;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubModule extends Model
{
    use HasFactory;
    protected $table = 'sub_modules';
    protected $fillable = [
        'name',
        'description',
        'status',
        'module_id',
        'created_by_id',
        'updated_by_id',
    ];
    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id');
    }
}
