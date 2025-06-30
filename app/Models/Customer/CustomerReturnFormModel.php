<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReturnFormModel extends Model
{
    use HasFactory;

    protected $table = 'customer_return_forms';

    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'remarks',
        'official_receipt_number',
        'attachment',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function customerReturnItem()
    {
        return $this->hasMany(CustomerReturnItemModel::class);
    }

    public static function onGenerateReferenceNumber()
    {
        $latestCustomerReturnId = static::latest()->value('id');
        $nextCustomerReturnId = $latestCustomerReturnId + 1;
        $referenceNumber = 'CR-' . str_pad($nextCustomerReturnId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
}
