<?php

namespace App\Services;

use App\Models\TimetableEntry;
use App\Models\TeacherAvailability;

/**
 * Detects soft constraint violations (preferences, optimization criteria)
 */
class ConflictDetector
{
    /**
     * Check for soft constraint violations
     */
    public function detectSoftViolations($assignment, $entries): array
    {
        $violations = [];

        if ($this->exceedsMaxPeriodsPerDay($assignment, $entries)) {
            $violations[] = [
                'type' => 'max_periods_exceeded',
                'severity' => 'soft',
                'message' => 'Teacher exceeds maximum periods per day',
            ];
        }

        if ($this->violatesFreePeriodPreference($assignment, $entries)) {
            $violations[] = [
                'type' => 'free_period_violation',
                'severity' => 'soft',
                'message' => 'Teacher has insufficient free periods',
            ];
        }

        if ($this->hasUnbalancedWorkload($assignment, $entries)) {
            $violations[] = [
                'type' => 'unbalanced_workload',
                'severity' => 'soft',
                'message' => 'Teacher workload is unbalanced across days',
            ];
        }

        if ($this->hasConsecutiveClasses($assignment, $entries)) {
            $violations[] = [
                'type' => 'consecutive_classes',
                'severity' => 'soft',
                'message' => 'Teacher has too many consecutive classes',
            ];
        }

        return $violations;
    }

    /**
     * Check if teacher exceeds max periods per day
     */
    private function exceedsMaxPeriodsPerDay($assignment, $entries): bool
    {
        $teacherAvailability = TeacherAvailability::where('teacher_id', $assignment['teacher_id'])
            ->where('day_of_week', $assignment['day_of_week'])
            ->first();

        if (!$teacherAvailability || !$teacherAvailability->max_periods_per_day) {
            return false;
        }

        $periodsOnDay = collect($entries)
            ->filter(fn($e) => $e['teacher_id'] === $assignment['teacher_id'] &&
                              $e['day_of_week'] === $assignment['day_of_week'])
            ->count() + 1; // +1 for the new assignment

        return $periodsOnDay > $teacherAvailability->max_periods_per_day;
    }

    /**
     * Check if teacher violates free period preference
     */
    private function violatesFreePeriodPreference($assignment, $entries): bool
    {
        $teacherAvailability = TeacherAvailability::where('teacher_id', $assignment['teacher_id'])
            ->where('day_of_week', $assignment['day_of_week'])
            ->first();

        if (!$teacherAvailability || !$teacherAvailability->min_free_periods) {
            return false;
        }

        // Total periods on day minus assigned periods = free periods
        $totalPeriodsOnDay = 6; // Typical school day
        $assignedOnDay = collect($entries)
            ->filter(fn($e) => $e['teacher_id'] === $assignment['teacher_id'] &&
                              $e['day_of_week'] === $assignment['day_of_week'])
            ->count() + 1;

        $freePeriods = $totalPeriodsOnDay - $assignedOnDay;

        return $freePeriods < $teacherAvailability->min_free_periods;
    }

    /**
     * Check if workload is unbalanced across days
     */
    private function hasUnbalancedWorkload($assignment, $entries): bool
    {
        $teacherWorkload = [];

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
            $teacherWorkload[$day] = collect($entries)
                ->filter(fn($e) => $e['teacher_id'] === $assignment['teacher_id'] &&
                                  $e['day_of_week'] === $day)
                ->count();
        }

        // Add current assignment
        $teacherWorkload[$assignment['day_of_week']]++;

        $avg = array_sum($teacherWorkload) / count($teacherWorkload);
        $maxDiff = max($teacherWorkload) - min($teacherWorkload);

        // Consider unbalanced if difference > 2
        return $maxDiff > 2;
    }

    /**
     * Check if teacher has too many consecutive classes
     */
    private function hasConsecutiveClasses($assignment, $entries): bool
    {
        $dayEntries = collect($entries)
            ->filter(fn($e) => $e['teacher_id'] === $assignment['teacher_id'] &&
                              $e['day_of_week'] === $assignment['day_of_week'])
            ->sortBy('time_slot_id')
            ->values()
            ->toArray();

        // Add new assignment and sort
        $dayEntries[] = $assignment;
        usort($dayEntries, fn($a, $b) => $a['time_slot_id'] <=> $b['time_slot_id']);

        // Check for 5+ consecutive classes
        $consecutive = 1;
        for ($i = 1; $i < count($dayEntries); $i++) {
            if ($dayEntries[$i]['time_slot_id'] == $dayEntries[$i-1]['time_slot_id'] + 1) {
                $consecutive++;
            } else {
                $consecutive = 1;
            }

            if ($consecutive >= 5) {
                return true;
            }
        }

        return false;
    }
}
