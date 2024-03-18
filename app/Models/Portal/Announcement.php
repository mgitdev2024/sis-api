<?php

namespace App\Models\Portal;

use App\Models\EmploymentInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;

class Announcement extends Model
{
    use HasFactory;
    protected $table = 'announcements';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'cover',
        'title',
        'description',
        'from',
        'to',
        'file',
        'is_allow_comment',
        'status',
        'type',
    ];
    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id', 'id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id', 'id');
    }
    public function createdByEmployment()
    {
        return $this->belongsTo(EmploymentInformation::class, 'created_by_id', 'personal_information_id');
    }
    public function updatedByEmployment()
    {
        return $this->belongsTo(EmploymentInformation::class, 'updated_by_id', 'personal_information_id');
    }
}
