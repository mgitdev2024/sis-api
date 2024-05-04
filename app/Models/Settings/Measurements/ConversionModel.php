<?php

namespace App\Models\Settings\Measurements;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversionModel extends Model
{
    use HasFactory;
    protected $table = 'conversions';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'conversion_short_uom',
        'conversion_long_uom',
        'status',
    ];

    public function createdBy()
    {
        return $this->belongsTo(CredentialModel::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(CredentialModel::class, 'updated_by_id');
    }
}
