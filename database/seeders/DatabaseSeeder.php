<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\CredentialSeeder;
use Database\Seeders\WmsSeeder\ItemVariantTypeMultiplierSeeder;
use Database\Seeders\WmsSeeder\ScmSystemSeeder;
use Database\Seeders\WmsSeeder\ModulePermissionSeeder;
use Database\Seeders\WmsSeeder\SubModulePermissionSeeder;
use Database\Seeders\WmsSeeder\PlantSeeder;
use Database\Seeders\WmsSeeder\UomSeeder;
use Database\Seeders\WmsSeeder\ConversionSeeder;
use Database\Seeders\WmsSeeder\StorageTypeSeeder;
use Database\Seeders\WmsSeeder\ItemCategorySeeder;
use Database\Seeders\WmsSeeder\ItemClassificationSeeder;
use Database\Seeders\WmsSeeder\ItemVariantTypeSeeder;
use Database\Seeders\WmsSeeder\DeliveryTypeSeeder;
use Database\Seeders\WmsSeeder\ItemMasterdataSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        #region Credential Seeder
        $this->call([
            CredentialSeeder::class
        ]);
        #endregion

        #region WMS Seeder
        $this->call([
            ScmSystemSeeder::class,
            ModulePermissionSeeder::class,
            SubModulePermissionSeeder::class,
            PlantSeeder::class,
            UomSeeder::class,
            ConversionSeeder::class,
            StorageTypeSeeder::class,
            ItemCategorySeeder::class,
            ItemClassificationSeeder::class,
            ItemVariantTypeSeeder::class,
            DeliveryTypeSeeder::class,
            ItemVariantTypeMultiplierSeeder::class,
            ItemMasterdataSeeder::class,
        ]);
        #endregion
    }
}
