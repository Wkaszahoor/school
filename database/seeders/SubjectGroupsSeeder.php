<?php

namespace Database\Seeders;

use App\Models\SubjectGroup;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Compulsory subjects for all (9-12)
        $compulsorySubjects = ['English', 'Urdu', 'Islamiat', 'Pakistan Studies'];

        // ICS Specific Subjects
        $icsCompulsory = ['Physics', 'Math', 'Chemistry'];
        $icsOptional = ['Computer Science'];  // Choose 1

        // PreMedical Specific Subjects
        $premedCompulsory = ['Physics', 'Chemistry', 'Biology'];
        $premedOptional = ['Math'];  // Choose 1 (optional in some schools)

        // Get subject IDs for mapping
        $subjectIds = Subject::whereIn('subject_name', array_merge(
            $compulsorySubjects, $icsCompulsory, $icsOptional, $premedCompulsory, $premedOptional
        ))->pluck('id', 'subject_name');

        // Grade 9-12 ICS Stream
        $icsGroup = SubjectGroup::updateOrCreate(
            ['group_slug' => 'ics-grade-9-12'],
            [
                'group_name' => 'ICS (Information & Computer Science)',
                'stream' => 'ICS',
                'description' => 'Subject group for ICS stream (Grade 9-12)',
                'is_active' => true,
                'min_select' => 7,  // 4 compulsory + 3 compulsory science
                'max_select' => 8,  // All compulsory + max 1 from optional
                'is_optional_group' => false,
            ]
        );

        // Attach compulsory subjects for ICS
        $icsSubjectData = [];
        foreach ($compulsorySubjects as $subjectName) {
            if (isset($subjectIds[$subjectName])) {
                $icsSubjectData[$subjectIds[$subjectName]] = ['subject_type' => 'compulsory'];
            }
        }
        foreach ($icsCompulsory as $subjectName) {
            if (isset($subjectIds[$subjectName])) {
                $icsSubjectData[$subjectIds[$subjectName]] = ['subject_type' => 'compulsory'];
            }
        }
        foreach ($icsOptional as $subjectName) {
            if (isset($subjectIds[$subjectName])) {
                $icsSubjectData[$subjectIds[$subjectName]] = ['subject_type' => 'optional'];
            }
        }
        $icsGroup->subjects()->sync($icsSubjectData);

        // Grade 9-12 PreMedical Stream
        $premedGroup = SubjectGroup::updateOrCreate(
            ['group_slug' => 'premedical-grade-9-12'],
            [
                'group_name' => 'PreMedical',
                'stream' => 'Pre-Medical',
                'description' => 'Subject group for Pre-Medical stream (Grade 9-12)',
                'is_active' => true,
                'min_select' => 7,  // 4 compulsory + 3 compulsory science
                'max_select' => 8,  // All compulsory + max 1 optional
                'is_optional_group' => false,
            ]
        );

        // Attach compulsory subjects for PreMedical
        $premedSubjectData = [];
        foreach ($compulsorySubjects as $subjectName) {
            if (isset($subjectIds[$subjectName])) {
                $premedSubjectData[$subjectIds[$subjectName]] = ['subject_type' => 'compulsory'];
            }
        }
        foreach ($premedCompulsory as $subjectName) {
            if (isset($subjectIds[$subjectName])) {
                $premedSubjectData[$subjectIds[$subjectName]] = ['subject_type' => 'compulsory'];
            }
        }
        foreach ($premedOptional as $subjectName) {
            if (isset($subjectIds[$subjectName])) {
                $premedSubjectData[$subjectIds[$subjectName]] = ['subject_type' => 'optional'];
            }
        }
        $premedGroup->subjects()->sync($premedSubjectData);

        echo "✅ Subject groups created for ICS and PreMedical streams\n";
    }
}
