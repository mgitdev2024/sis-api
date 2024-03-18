<?php

namespace App\Models\SystemConfiguration;

use App\Models\Credential;
use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    use HasFactory;

    protected $table = 'user_access';

    protected $fillable = [
        'credential_id',
        'access_management_id',
        'customized_user_access',
        'created_by_id',
        'updated_by_id'
    ];

    public function credential()
    {
        return $this->belongsTo(Credential::class, 'credential_id');
    }

    public function accessManagement()
    {
        return $this->belongsTo(AccessManagement::class, 'access_management_id');
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
