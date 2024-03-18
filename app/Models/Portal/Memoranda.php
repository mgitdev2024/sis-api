<?php

namespace App\Models\Portal;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memoranda extends Model
{
    use HasFactory;
    protected $table = 'memoranda';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'reference_number',
        'subject',
        'description',
        'from',
        'to',
        'effective_date',
        'file',
        'is_pinned',
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
