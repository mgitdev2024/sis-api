<?php

namespace App\Models\Items;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemClassification extends Model
{
    use HasFactory;

    protected $table = 'item_classifications';
    protected $fillable = [
        'name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function createdBy()
    {
        return $this->belongsTo(Credential::class, 'created_by_id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(Credential::class, 'updated_by_id');
    }
}
