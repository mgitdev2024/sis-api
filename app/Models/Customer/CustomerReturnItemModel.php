<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReturnItemModel extends Model
{
    use HasFactory;

    protected $table = 'customer_return_items';

    protected $fillable = [
        'customer_return_form_id',
        'item_code',
        'item_description',
        'item_category_name',
        'quantity',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function customerReturnForm()
    {
        return $this->belongsTo(CustomerReturnFormModel::class, 'customer_return_form_id');
    }
}
