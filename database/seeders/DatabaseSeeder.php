<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\CredentialSeeder;
use Database\Seeders\SubModulePermissionSeeder;
use Database\Seeders\ModulePermissionSeeder;

use Database\Seeders\WmsSeeder\ItemMovementSeeder;
use Database\Seeders\WmsSeeder\ItemVariantTypeMultiplierSeeder;
use Database\Seeders\WmsSeeder\ScmSystemSeeder;
use Database\Seeders\WmsSeeder\StockTypeSeeder;
use Database\Seeders\WmsSeeder\StorageWarehouseSeeder;
use Database\Seeders\WmsSeeder\FacilityPlantSeeder;
use Database\Seeders\WmsSeeder\SubLocationTypeSeeder;
use Database\Seeders\WmsSeeder\SubLocationSeeder;
use Database\Seeders\WmsSeeder\UomSeeder;
use Database\Seeders\WmsSeeder\ConversionSeeder;
use Database\Seeders\WmsSeeder\StorageTypeSeeder;
use Database\Seeders\WmsSeeder\ItemCategorySeeder;
use Database\Seeders\WmsSeeder\ItemClassificationSeeder;
use Database\Seeders\WmsSeeder\ItemVariantTypeSeeder;
use Database\Seeders\WmsSeeder\DeliveryTypeSeeder;
use Database\Seeders\WmsSeeder\ItemMasterdataSeeder;
use Database\Seeders\WmsSeeder\ZoneSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        #region Admin Seeder
        $this->call([
            AdminSystemSeeder::class,
        ]);
        #endregion
    }
}
