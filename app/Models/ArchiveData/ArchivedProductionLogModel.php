<?php

namespace App\Models\ArchiveData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivedProductionLogModel extends Model
{
    use HasFactory;
    protected $connection = 'log_mysql';

    protected $table = 'archived_production_logs';

    protected $fillable = [
        'entity_model',
        'entity_id',
        'item_key',
        'data',
        'action',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
