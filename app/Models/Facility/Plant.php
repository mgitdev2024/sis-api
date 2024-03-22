<?php

namespace App\Models\Facility;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;
    protected $table = 'plants';
    protected $fillable = [
        'short_name',
        'long_name',
        'plant_code',
        'description',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function createdBy()
    {
        return $this->belongsTo(Credential::class, 'created_by_id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(Credential::class, 'updated_by_id');
    }
}
