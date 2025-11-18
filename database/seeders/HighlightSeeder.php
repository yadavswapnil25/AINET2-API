<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Highlight;

class HighlightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default highlight
        Highlight::updateOrCreate(
            ['heading' => 'HIGHLIGHTS'],
            [
                'heading' => 'HIGHLIGHTS',
                'subheading' => '9th AINET International Conference 2026 - To Be Announced SOON',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }
}
