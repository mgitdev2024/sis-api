<?php

namespace App\Models\Settings\Items;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemClassificationModel extends Model
{
    use HasFactory;

    protected $table = 'item_classifications';
    protected $fillable = [
        'name',
        'sticker_multiplier',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function createdBy()
    {
        return $this->belongsTo(CredentialModel::class, 'created_by_id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(CredentialModel::class, 'updated_by_id');
    }
}
