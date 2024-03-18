<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OrganizationalStructure\JobTitle;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'section_id' => null,
                'division_id' => null,
                'department_id' => null,
                'job_code' => 'mg-ceo',
                'job_title' => 'Chief Executive Officer',
                'job_description' => null,
                'slot' => 1,
                'status' => 1,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'section_id' => null,
                'division_id' => null,
                'department_id' => null,
                'job_code' => 'mg-pres',
                'job_title' => 'President',
                'job_description' => null,
                'slot' => 1,
                'status' => 1,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'section_id' => null,
                'division_id' => null,
                'department_id' => null,
                'job_code' => 'mg-vp',
                'job_title' => 'Vice President',
                'job_description' => null,
                'slot' => 1,
                'status' => 1,
            ]
        ];
        foreach ($data as $item) {
            JobTitle::create($item);
        }
    }
}
