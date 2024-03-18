<?php

namespace App\Models\OrganizationalStructure;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;


class Company extends Model
{
    use HasFactory;
    protected $table = 'companies';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'company_code',
        'company_short_name',
        'company_long_name',
        'company_level',
        'tin_no',
        'sec_no',
        'sec_registered_date',
        'registered_address',
        'transactional_considerations',
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
