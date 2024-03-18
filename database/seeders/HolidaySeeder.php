<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Portal\Holiday;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = date('Y');
        $data = [
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'New Year\'s Day',
                'description' => 'Start the year with celebration!',
                'location' => 'Nationwide',
                'date' => $currentYear . '-01-01',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'Holy Week (Maundy Thursday)',
                'description' => 'Observance of Holy Thursday',
                'location' => 'Nationwide',
                'date' => $currentYear . '-04-06',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'Good Friday',
                'description' => 'Commemoration of Good Friday',
                'location' => 'Nationwide',
                'date' => $currentYear . '-04-07',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'Labor Day',
                'description' => 'Celebration of workers',
                'location' => 'Nationwide',
                'date' => $currentYear . '-05-01',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'Independence Day',
                'description' => 'Celebrating the nation\'s independence!',
                'location' => 'Nationwide',
                'date' => $currentYear . '-06-12',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'National Heroes Day',
                'description' => 'Honoring Filipino heroes',
                'location' => 'Nationwide',
                'date' => $currentYear . '-08-28',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'Bonifacio Day',
                'description' => 'Celebration of Andres Bonifacio\'s birth',
                'location' => 'Nationwide',
                'date' => $currentYear . '-11-30',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'Christmas Day',
                'description' => 'Celebrate the joy of Christmas!',
                'location' => 'Nationwide',
                'date' => $currentYear . '-12-25',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null,
                'title' => 'Rizal Day',
                'description' => 'Commemoration of Jose Rizal\'s life',
                'location' => 'Nationwide',
                'date' => $currentYear . '-12-30',
                'is_special' => 0,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null, //nullable
                'title' => 'Ninoy Aquino Day',
                'description' => 'Celebrating the Ninoy Aquino Day', //nullable
                'location' => 'Nationwide', //nullable
                'date' => $currentYear . '-08-21',
                'is_special' => 1,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null, //nullable
                'title' => 'EDSA People Power Revolution Anniversary',
                'description' => 'Celebrating the EDSA People Power Revolution Anniversary', //nullable
                'location' => 'Nationwide', //nullable
                'date' => $currentYear . '-02-24',
                'is_special' => 1,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null, //nullable
                'title' => 'Black Saturday',
                'description' => 'Celebrating Black Saturday', //nullable
                'location' => 'Nationwide', //nullable
                'date' => $currentYear . '-04-08',
                'is_special' => 1,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null, //nullable
                'title' => 'All Saint\'s Day',
                'description' => 'Celebrating All Saints Day', //nullable
                'location' => 'Nationwide', //nullable
                'date' => $currentYear . '-11-01',
                'is_special' => 1,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null, //nullable
                'title' => 'Feast of the Immaculate Conception of Mary',
                'description' => 'Celebrating the Feast of the Immaculate Conception of Mary', //nullable
                'location' => 'Nationwide', //nullable
                'date' => $currentYear . '-12-08',
                'is_special' => 1,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null, //nullable
                'title' => 'Last Day of the Year',
                'description' => 'Celebrating the end of Year', //nullable
                'location' => 'Nationwide', //nullable
                'date' => $currentYear . '-12-31',
                'is_special' => 1,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null, //nullable
                'title' => 'Additional Special Holiday - All Souls Day',
                'description' => 'Celebrating All Souls Day', //nullable
                'location' => 'Nationwide', //nullable
                'date' => $currentYear . '-11-02',
                'is_special' => 1,
                'is_local' => 0,
            ],
            [
                'created_by_id' => 1,
                'updated_by_id' => null, //nullable
                'title' => 'Additional Special Holiday - Barangay and SK Election',
                'description' => 'Practice your right to vote', //nullable
                'location' => 'Nationwide', //nullable
                'date' => $currentYear . '-10-30',
                'is_special' => 1,
                'is_local' => 0,
            ],
            // Add more holidays as needed
        ];
        foreach ($data as $item) {
            Holiday::create($item);
        }
    }
}
