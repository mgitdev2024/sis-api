<?php

namespace App\Models\History;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PrintHistoryModel extends Model
{
    use HasFactory;
    protected $table = 'print_history';
    protected $fillable = [
        'production_batch_id',
        'produce_items',
        'is_reprint',
        'reason',
        'attachment',
    ];
}