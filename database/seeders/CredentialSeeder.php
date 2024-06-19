<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CredentialModel;

class CredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $users = [
            [
                'employee_id' => "0000",
            ],
            [
                'employee_id' => "0001", // SCM PLANNER
            ],
            [
                'employee_id' => "0002", // SCM TEAM LEADER OTA
            ],
            [
                'employee_id' => "0003",// SCM TEAM LEADER OTB
            ],
            [
                'employee_id' => "0004",
            ],
            [
                'employee_id' => "0005",
            ],
            [
                'employee_id' => "0006",
            ],
            [
                'employee_id' => "0006",
            ],
        ];



        foreach ($users as $value) {
            CredentialModel::create([
                'employee_id' => $value['employee_id'],
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
