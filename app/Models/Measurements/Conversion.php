<?php

namespace App\Models\Measurements;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'primary_short_uom',
        'secondary_short_uom',
        'primary_long_uom',
        'secondary_long_uom',
        'status',
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
