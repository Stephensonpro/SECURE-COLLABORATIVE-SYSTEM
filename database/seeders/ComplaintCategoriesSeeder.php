<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ComplaintCategory;

class ComplaintCategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            // Airline-related categories
            ['name' => 'Flight Cancellation', 'responsible_party' => 'airline'],
            ['name' => 'Flight Delay', 'responsible_party' => 'airline'],
            ['name' => 'Baggage Issues', 'responsible_party' => 'airline'],
            ['name' => 'In-flight Service', 'responsible_party' => 'airline'],
            ['name' => 'Seat Issues', 'responsible_party' => 'airline'],
            ['name' => 'Ticketing Issues', 'responsible_party' => 'airline'],
            ['name' => 'Boarding Problems', 'responsible_party' => 'airline'],

            // Airport-related categories
            ['name' => 'Security Screening', 'responsible_party' => 'airport'],
            ['name' => 'Immigration/Customs', 'responsible_party' => 'airport'],
            ['name' => 'Airport Facilities', 'responsible_party' => 'airport'],
            ['name' => 'Parking Issues', 'responsible_party' => 'airport'],
            ['name' => 'Ground Transportation', 'responsible_party' => 'airport'],
            ['name' => 'Lost and Found', 'responsible_party' => 'airport'],
        ];

        foreach ($categories as $category) {
            ComplaintCategory::create($category);
        }
    }
}
