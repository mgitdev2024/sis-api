<?php

namespace Database\Seeders;

use App\Models\ContactNumber;
use App\Models\EmergencyContact;
use App\Models\EmploymentInformation;
use App\Models\GovernmentInformation;
use App\Models\PersonalInformation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Credential;

class CredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Credential::create([
            'employee_id' => "0000",
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
