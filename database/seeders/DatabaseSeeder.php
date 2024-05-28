<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\MosSeeder\ScmSystemSeeder;
use Database\Seeders\MosSeeder\ModulePermissionSeeder;
use Database\Seeders\MosSeeder\SubModulePermissionSeeder;
use Database\Seeders\MosSeeder\CredentialSeeder;
use Database\Seeders\MosSeeder\PlantSeeder;
use Database\Seeders\MosSeeder\UomSeeder;
use Database\Seeders\MosSeeder\ConversionSeeder;
use Database\Seeders\MosSeeder\StorageTypeSeeder;
use Database\Seeders\MosSeeder\ItemCategorySeeder;
use Database\Seeders\MosSeeder\ItemClassificationSeeder;
use Database\Seeders\MosSeeder\ItemVariantTypeSeeder;
use Database\Seeders\MosSeeder\DeliveryTypeSeeder;
use Database\Seeders\MosSeeder\ItemMasterdataSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        #region MOS Seeder
        $this->call([
            ScmSystemSeeder::class,
            ModulePermissionSeeder::class,
            SubModulePermissionSeeder::class,
            CredentialSeeder::class,
            PlantSeeder::class,
            UomSeeder::class,
            ConversionSeeder::class,
            StorageTypeSeeder::class,
            ItemCategorySeeder::class,
            ItemClassificationSeeder::class,
            ItemVariantTypeSeeder::class,
            DeliveryTypeSeeder::class,
            ItemMasterdataSeeder::class,
        ]);
        #endregion
    }
}
