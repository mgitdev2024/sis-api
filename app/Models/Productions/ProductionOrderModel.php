<?php

namespace App\Models\Productions;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrderModel extends Model
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
        return $this->belongsTo(CredentialModel::class, 'created_by_id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(CredentialModel::class, 'updated_by_id');
    }
    public function productionOta()
    {
        return $this->hasMany(ProductionOTAModel::class, 'production_order_id', 'id');
    }

    public function productionOtb()
    {
        return $this->hasMany(ProductionOTBModel::class, 'production_order_id', 'id');
    }

    public function getStatusLabelAttribute()
    {
        return $this->status == 1 ? 'Complete' : 'Pending';
    }

    public static function onGenerateProductionReferenceNumber()
    {
        $latestProductionOrder = static::latest()->value('id');
        $nextProductionOrder = $latestProductionOrder + 1;
        $referenceNumber = '9' . str_pad($nextProductionOrder, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
}
