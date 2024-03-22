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
        CredentialModel::create([
            'employee_id' => "0000",
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
