<?php

namespace Database\Seeders;

use App\Models\OrganizationalStructure\OrganizationalStructure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationalStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'parent_id' => null,
                'job_id' => 1,
                'status' => 1,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'parent_id' => 1,
                'job_id' => 2,
                'status' => 1,
            ],
        ];
        foreach ($data as $item) {
            OrganizationalStructure::create($item);
        }
    }
}
