<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed common academic years
        $academicYears = [
            [
                'year' => '2023-24',
                'start_date' => '2023-04-01',
                'end_date' => '2024-03-31',
                'is_active' => false,
                'description' => 'Academic Year 2023-24',
            ],
            [
                'year' => '2024-25',
                'start_date' => '2024-04-01',
                'end_date' => '2025-03-31',
                'is_active' => true,
                'description' => 'Academic Year 2024-25',
            ],
            [
                'year' => '2025-26',
                'start_date' => '2025-04-01',
                'end_date' => '2026-03-31',
                'is_active' => true,
                'description' => 'Academic Year 2025-26',
            ],
            [
                'year' => '2026-27',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_active' => false,
                'description' => 'Academic Year 2026-27',
            ],
        ];

        foreach ($academicYears as $year) {
            AcademicYear::firstOrCreate(
                ['year' => $year['year']],
                $year
            );
        }
    }
}
