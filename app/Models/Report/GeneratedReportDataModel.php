<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedReportDataModel extends Model
{
    use HasFactory;

    protected $table = 'generated_report_data';

    protected $fillable = [
        'model_name',
        'store_code',
        'store_sub_unit_short_name',
        'report_data',
        'date_range',
        'uuid',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];
}
