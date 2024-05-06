<?php

namespace App\Models\History;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionHistoricalLogModel extends Model
{
    use HasFactory;
    protected $table = 'production_historical_logs';
    protected $fillable = [
        'entity_model',
        'entity_id',
        'item_key',
        'data',
        'action',
        'created_by_id',
        'updated_by_id',
    ];
}
