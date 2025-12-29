<?php

namespace App\Models\Sap\PurchaseRequest;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestTemplateModel extends Model
{
    use HasFactory;
    protected $table = 'purchase_request_template';

    protected $fillable = [
        'store_code',
        'store_sub_unit_short_name',
        'selection_template',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];
}
