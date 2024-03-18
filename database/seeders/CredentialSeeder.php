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
            'is_first_login' => 0,
            'user_access_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PersonalInformation::create([
            'employee_id' => "0000",
            'first_name' => 'Super',
            'middle_name' => '',
            'last_name' => 'Admin',
            'gender' => 'Male',
            'birth_date' => '2023-12-25',
            'age' => '99',
            'marital_status' => 'Single',
            'personal_email' => 'it.dev@marygracecafe.com',
            'company_email' => 'it.dev@marygracecafe.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ContactNumber::create([
            'personal_information_id' => 1,
            'phone_number' => '09954611504',
            'type' => '0',
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        EmergencyContact::create([
            'personal_information_id' => 1,
            'name' => 'Juan Miguel Casta Garcia',
            'phone_number' => '09954611504',
            'relationship' => 'Developer',
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        GovernmentInformation::create([
            'personal_information_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        EmploymentInformation::create([
            'personal_information_id' => 1,
            'company_id' => 'Mary Grace',
            'branch_id' => 'Head Office',
            'department_id' => 'IT Department',
            'section_id' => 'System Developer',
            'position_id' => null,
            'workforce_division_id' => 'Super Admin',
            'employment_classification' => 'Super Admin',
            'date_hired' => '2023-12-25',
            'onboarding_status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}
