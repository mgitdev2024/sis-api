<?php

namespace App\Models\WMS\Warehouse;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseBulkPutAwayModel extends Model
{
    use HasFactory;

    protected $table = 'wms_warehouse_put_away_bulk'; // Specify the table name

    protected $fillable = [
        'temporary_storages',
        'sub_location_id',
    ];

    // Relationship to Sub Location
    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'sub_location_id');
    }
}
