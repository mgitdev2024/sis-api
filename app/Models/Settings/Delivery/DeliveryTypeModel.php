<?php

namespace App\Models\Settings\Delivery;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTypeModel extends Model
{
    use HasFactory;
    protected $table = 'delivery_types';
    protected $fillable = [
        'type',
        'description',
        'created_by_id',
        'updated_by_id',
        'status'
    ];



}
