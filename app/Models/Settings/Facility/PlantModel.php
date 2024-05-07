<?php

namespace App\Models\Settings\Facility;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantModel extends Model
{
    use HasFactory;
    protected $table = 'plants';
    protected $fillable = [
        'short_name',
        'long_name',
        'plant_code',
        'description',
        'created_by_id',
        'updated_by_id',
        'status'
    ];



}
