<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\SchoolClass;
use App\Models\ClassStreamSubjectGroup;
use Illuminate\Database\Seeder;

/**
 * Pakistani Education System Seeder
 *
 * Implements complete subject and group structure for classes 9-12:
 *
 * SSC (Secondary School Certificate) - Classes 9-10:
 * - Pre-Medical: Biology, Physics, Chemistry, Mathematics
 * - ICS: Computer Science, Physics, Chemistry, Mathematics
 * (Plus compulsory: English, Urdu, Islamiat for 9; English, Urdu, Pakistan Studies for 10)
 *
 * HSSC (Higher Secondary School Certificate) - Classes 11-12:
 * - ICS: Computer Science, Mathematics, Physics
 * - Pre-Medical: Biology, Physics, Chemistry
 * (Plus compulsory: English, Urdu, Islamiat, Pakistan Studies)
 */
class PakistaniEducationSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure all required subjects exist
        $this->ensureSubjectsExist();

        // Get all subject IDs for mapping
        $subjects = Subject::all()->keyBy('subject_name');

        // Create subject groups for each class
        $this->createClassGroupStructure($subjects);

        echo "✅ Pakistani Education System configured for all classes (9-12)\n";
    }

    /**
     * Ensure all required subjects exist in the database
     */
    private function ensureSubjectsExist(): void
    {
        $requiredSubjects = [
            // Core compulsory subjects (all classes)
            'English' => 'ENG',
            'Urdu' => 'URD',
            'Islamiat' => 'ISL',
            'Pakistan Studies' => 'PKS',
            'Mathematics' => 'MAT',
            'Physical Education' => 'PE',

            // Science subjects
            'Physics' => 'PHY',
            'Chemistry' => 'CHE',
            'Biology' => 'BIO',

            // Arts/Social subjects
            'History' => 'HIS',
            'Geography' => 'GEO',
            'Economics' => 'ECO',

            // Technology subjects
            'Computer Science' => 'CS',
            'Additional Mathematics' => 'AMAT',
        ];

        foreach ($requiredSubjects as $name => $code) {
            $existing = Subject::where('subject_code', $code)->first();
            if ($existing) {
                $existing->update(['subject_name' => $name, 'is_active' => true]);
            } else {
                Subject::create(['subject_name' => $name, 'subject_code' => $code, 'is_active' => true]);
            }
        }
    }

    /**
     * Create subject groups for all classes 9-12
     */
    private function createClassGroupStructure($subjects): void
    {
        $classes = SchoolClass::whereIn('class', [9, 10, '1st Year', '2nd Year'])->get();

        foreach ($classes as $class) {
            if ($class->class == 9) {
                $this->createClass9Groups($class, $subjects);
            } elseif ($class->class == 10) {
                $this->createClass10Groups($class, $subjects);
            } else {
                $this->createClass11to12Groups($class, $subjects);
            }
        }
    }

    /**
     * Class 9 Groups (SSC)
     * Compulsory: English, Urdu, Islamiat, Mathematics
     * Streams: Pre-Medical, ICS
     */
    private function createClass9Groups($class, $subjects): void
    {
        // ========== CLASS 9 PRE-MEDICAL GROUP ==========
        $premedGroup = SubjectGroup::updateOrCreate(
            ['group_slug' => "premedical-9"],
            [
                'group_name' => "Pre-Medical (Class 9) - SSC",
                'stream' => 'Pre-Medical',
                'description' => "Pre-Medical (SSC) - Compulsory: English, Urdu, Islamiat, Mathematics. Elective: Biology, Physics, Chemistry",
                'is_active' => true,
                'min_select' => 7,
                'max_select' => 7,
                'is_optional_group' => false,
                'education_level' => 'SSC',
            ]
        );

        $premedSubjects = [
            'English' => 'compulsory',
            'Urdu' => 'compulsory',
            'Islamiat' => 'compulsory',
            'Mathematics' => 'compulsory',
            'Physics' => 'major',
            'Chemistry' => 'major',
            'Biology' => 'major',
        ];

        $this->attachSubjectsToGroup($premedGroup, $premedSubjects, $subjects);

        ClassStreamSubjectGroup::updateOrCreate(
            ['class_id' => $class->id, 'stream_key' => 'pre-medical'],
            ['group_id' => $premedGroup->id]
        );

        // ========== CLASS 9 ICS GROUP ==========
        $icsGroup = SubjectGroup::updateOrCreate(
            ['group_slug' => "ics-9"],
            [
                'group_name' => "ICS (Class 9) - SSC",
                'stream' => 'ICS',
                'description' => "ICS (SSC) - Compulsory: English, Urdu, Islamiat, Mathematics. Elective: Computer Science, Physics, Chemistry",
                'is_active' => true,
                'min_select' => 7,
                'max_select' => 7,
                'is_optional_group' => false,
                'education_level' => 'SSC',
            ]
        );

        $icsSubjects = [
            'English' => 'compulsory',
            'Urdu' => 'compulsory',
            'Islamiat' => 'compulsory',
            'Mathematics' => 'compulsory',
            'Computer Science' => 'major',
            'Physics' => 'major',
            'Chemistry' => 'major',
        ];

        $this->attachSubjectsToGroup($icsGroup, $icsSubjects, $subjects);

        ClassStreamSubjectGroup::updateOrCreate(
            ['class_id' => $class->id, 'stream_key' => 'ics'],
            ['group_id' => $icsGroup->id]
        );
    }

    /**
     * Class 10 Groups (SSC)
     * Compulsory: English, Urdu, Pakistan Studies, Mathematics
     * Streams: Pre-Medical, ICS
     */
    private function createClass10Groups($class, $subjects): void
    {
        // ========== CLASS 10 PRE-MEDICAL GROUP ==========
        $premedGroup = SubjectGroup::updateOrCreate(
            ['group_slug' => "premedical-10"],
            [
                'group_name' => "Pre-Medical (Class 10) - SSC",
                'stream' => 'Pre-Medical',
                'description' => "Pre-Medical (SSC) - Compulsory: English, Urdu, Pakistan Studies, Mathematics. Elective: Biology, Physics, Chemistry",
                'is_active' => true,
                'min_select' => 7,
                'max_select' => 7,
                'is_optional_group' => false,
                'education_level' => 'SSC',
            ]
        );

        $premedSubjects = [
            'English' => 'compulsory',
            'Urdu' => 'compulsory',
            'Pakistan Studies' => 'compulsory',
            'Mathematics' => 'compulsory',
            'Physics' => 'major',
            'Chemistry' => 'major',
            'Biology' => 'major',
        ];

        $this->attachSubjectsToGroup($premedGroup, $premedSubjects, $subjects);

        ClassStreamSubjectGroup::updateOrCreate(
            ['class_id' => $class->id, 'stream_key' => 'pre-medical'],
            ['group_id' => $premedGroup->id]
        );

        // ========== CLASS 10 ICS GROUP ==========
        $icsGroup = SubjectGroup::updateOrCreate(
            ['group_slug' => "ics-10"],
            [
                'group_name' => "ICS (Class 10) - SSC",
                'stream' => 'ICS',
                'description' => "ICS (SSC) - Compulsory: English, Urdu, Pakistan Studies, Mathematics. Elective: Computer Science, Physics, Chemistry",
                'is_active' => true,
                'min_select' => 7,
                'max_select' => 7,
                'is_optional_group' => false,
                'education_level' => 'SSC',
            ]
        );

        $icsSubjects = [
            'English' => 'compulsory',
            'Urdu' => 'compulsory',
            'Pakistan Studies' => 'compulsory',
            'Mathematics' => 'compulsory',
            'Computer Science' => 'major',
            'Physics' => 'major',
            'Chemistry' => 'major',
        ];

        $this->attachSubjectsToGroup($icsGroup, $icsSubjects, $subjects);

        ClassStreamSubjectGroup::updateOrCreate(
            ['class_id' => $class->id, 'stream_key' => 'ics'],
            ['group_id' => $icsGroup->id]
        );
    }

    /**
     * Classes 11-12 Groups (HSSC)
     * Compulsory: English, Urdu, Islamiat, Pakistan Studies
     * Streams: ICS, Pre-Medical
     */
    private function createClass11to12Groups($class, $subjects): void
    {
        // ========== ICS GROUP (HSSC) ==========
        $icsGroup = SubjectGroup::updateOrCreate(
            ['group_slug' => "ics-{$class->class}"],
            [
                'group_name' => "ICS (Class {$class->class}) - HSSC",
                'stream' => 'ICS',
                'description' => "ICS (HSSC) - Compulsory: English, Urdu, Islamiat, Pakistan Studies. Elective: Computer Science, Mathematics, Physics",
                'is_active' => true,
                'min_select' => 7,
                'max_select' => 7,
                'is_optional_group' => false,
                'education_level' => 'HSSC',
            ]
        );

        $icsSubjects = [
            'English' => 'compulsory',
            'Urdu' => 'compulsory',
            'Islamiat' => 'compulsory',
            'Pakistan Studies' => 'compulsory',
            'Computer Science' => 'major',
            'Mathematics' => 'major',
            'Physics' => 'major',
        ];

        $this->attachSubjectsToGroup($icsGroup, $icsSubjects, $subjects);

        ClassStreamSubjectGroup::updateOrCreate(
            ['class_id' => $class->id, 'stream_key' => 'ics'],
            ['group_id' => $icsGroup->id]
        );

        // ========== PRE-MEDICAL GROUP (HSSC) ==========
        $premedGroup = SubjectGroup::updateOrCreate(
            ['group_slug' => "premedical-{$class->class}"],
            [
                'group_name' => "Pre-Medical (Class {$class->class}) - HSSC",
                'stream' => 'Pre-Medical',
                'description' => "Pre-Medical (HSSC) - Compulsory: English, Urdu, Islamiat, Pakistan Studies. Elective: Biology, Physics, Chemistry",
                'is_active' => true,
                'min_select' => 7,
                'max_select' => 7,
                'is_optional_group' => false,
                'education_level' => 'HSSC',
            ]
        );

        $premedSubjects = [
            'English' => 'compulsory',
            'Urdu' => 'compulsory',
            'Islamiat' => 'compulsory',
            'Pakistan Studies' => 'compulsory',
            'Biology' => 'major',
            'Physics' => 'major',
            'Chemistry' => 'major',
        ];

        $this->attachSubjectsToGroup($premedGroup, $premedSubjects, $subjects);

        ClassStreamSubjectGroup::updateOrCreate(
            ['class_id' => $class->id, 'stream_key' => 'pre-medical'],
            ['group_id' => $premedGroup->id]
        );
    }

    /**
     * Helper function to attach subjects to a group
     */
    private function attachSubjectsToGroup($group, $subjectList, $subjects): void
    {
        $groupSubjects = [];
        foreach ($subjectList as $subjectName => $type) {
            if (isset($subjects[$subjectName])) {
                $groupSubjects[$subjects[$subjectName]->id] = ['subject_type' => $type];
            }
        }
        $group->subjects()->sync($groupSubjects);
    }
}
