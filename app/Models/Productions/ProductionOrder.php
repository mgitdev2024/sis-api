<?php

namespace App\Models\Productions;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    use HasFactory;

    protected $table = 'production_orders';
    protected $fillable = [
        'reference_number',
        'production_date',
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function createdBy()
    {
        return $this->belongsTo(Credential::class, 'created_by_id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(Credential::class, 'updated_by_id');
    }

    public static function onGenerateProductionReferenceNumber()
    {
        $latestProductionOrder = static::latest()->value('id');
        $nextProductionOrder = $latestProductionOrder + 1;
        $referenceNumber = '9' . str_pad($nextProductionOrder, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
}
