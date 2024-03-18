<?php

namespace App\Models\Store;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $table = 'stores';

    protected $fillable = [
        'short_name',
        'long_name',
        'store_code',
        'store_type',
        'store_branch',
        'store_area',
        'store_status',
        'created_by_id',
        'updated_by_id',
    ];

    protected $appends = ['store_type_label'];

    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id', 'id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id', 'id');
    }
    public function getStoreTypeLabelAttribute()
    {
        $store_type_label = ["0" => "Kiosk", "1" => "Cafe"];
        return isset($store_type_label[$this->store_type]) ? $store_type_label[$this->store_type] : "Unknown";
    }
}
