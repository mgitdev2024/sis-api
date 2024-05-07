<?php

namespace App\Models\Settings\Measurements;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UomModel extends Model
{
    use HasFactory;

    protected $table = 'uom';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'short_uom',
        'long_uom',
        'status',
    ];




}
