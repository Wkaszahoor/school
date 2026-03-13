<?php

namespace App\Services;

use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\RoomConfiguration;

/**
 * Constraint Satisfaction Problem (CSP) Engine
 * Uses backtracking with MRV (Minimum Remaining Values) heuristic
 * and LCV (Least Constraining Value) ordering
 */
class CSPEngine
{
    private ConstraintValidator $validator;
    private ConstraintPropagator $propagator;
    private ConflictDetector $conflictDetector;
    private $assignment = [];
    private $domains = [];
    private $variables = [];
    private $stats = [
        'variables' => 0,
        'backtracks' => 0,
        'assignments' => 0,
    ];

    public function __construct(
        ConstraintValidator $validator,
        ConstraintPropagator $propagator,
        ConflictDetector $conflictDetector
    ) {
        $this->validator = $validator;
        $this->propagator = $propagator;
        $this->conflictDetector = $conflictDetector;
    }

    /**
     * Main solving method using backtracking
     */
    public function solve($timetableId): array
    {
        $timetable = Timetable::find($timetableId);
        if (!$timetable) {
            return ['success' => false, 'error' => 'Timetable not found'];
        }

        // Initialize variables (all assignments to be made)
        $this->initializeVariables($timetable);

        // Initialize domains (possible values for each variable)
        $this->initializeDomains($timetable);

        // Apply constraint propagation
        if (!$this->propagateConstraints()) {
            return [
                'success' => false,
                'error' => 'No solution possible (constraint propagation failed)',
                'stats' => $this->stats
            ];
        }

        // Run backtracking search
        if ($this->backtrack($timetable)) {
            // Save assignments to database
            $this->saveAssignments($timetable);
            return [
                'success' => true,
                'message' => 'Timetable generated successfully',
                'stats' => $this->stats,
                'assignments' => count($this->assignment)
            ];
        }

        return [
            'success' => false,
            'error' => 'Could not find a valid solution after backtracking',
            'stats' => $this->stats
        ];
    }

    /**
     * Initialize variables (assignments needed)
     */
    private function initializeVariables($timetable): void
    {
        $classes = SchoolClass::all();
        $subjects = Subject::all();

        foreach ($classes as $class) {
            foreach ($subjects as $subject) {
                // Check if this class needs this subject
                if ($this->classNeedsSubject($class, $subject)) {
                    $this->variables[] = [
                        'id' => "{$class->id}_{$subject->id}",
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                    ];
                }
            }
        }

        $this->stats['variables'] = count($this->variables);
    }

    /**
     * Check if a class needs a subject
     */
    private function classNeedsSubject($class, $subject): bool
    {
        // Get the class's assigned subjects
        $classSubjects = $class->subjects()->pluck('subject_id')->toArray();
        return in_array($subject->id, $classSubjects);
    }

    /**
     * Initialize domains (possible values for each variable)
     */
    private function initializeDomains($timetable): void
    {
        $teachers = User::where('role', 'teacher')->get();
        $rooms = RoomConfiguration::active()->get();
        $timeSlots = TimeSlot::active()->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($this->variables as $variable) {
            $domain = [];

            foreach ($teachers as $teacher) {
                foreach ($rooms as $room) {
                    foreach ($timeSlots as $slot) {
                        foreach ($days as $day) {
                            $domain[] = [
                                'teacher_id' => $teacher->id,
                                'room_id' => $room->id,
                                'time_slot_id' => $slot->id,
                                'day_of_week' => $day,
                            ];
                        }
                    }
                }
            }

            $this->domains[$variable['id']] = $domain;
        }
    }

    /**
     * Apply constraint propagation using AC-3
     */
    private function propagateConstraints(): bool
    {
        // Build constraints list
        $constraints = [];
        for ($i = 0; $i < count($this->variables) - 1; $i++) {
            for ($j = $i + 1; $j < count($this->variables); $j++) {
                $constraints[] = [
                    'xi' => $this->variables[$i]['id'],
                    'xj' => $this->variables[$j]['id'],
                ];
            }
        }

        return $this->propagator->propagate($this->domains, $constraints);
    }

    /**
     * Backtracking search algorithm
     */
    private function backtrack($timetable): bool
    {
        // All variables assigned?
        if (count($this->assignment) === count($this->variables)) {
            return true;
        }

        // Select unassigned variable using MRV heuristic
        $variable = $this->selectUnassignedVariable();
        if (!$variable) {
            return false;
        }

        // Try values in order using LCV heuristic
        $orderedValues = $this->orderDomainValues($variable);

        foreach ($orderedValues as $value) {
            if ($this->isConsistentAssignment($variable, $value)) {
                // Make assignment
                $this->assignment[$variable['id']] = $value;
                $this->stats['assignments']++;

                // Recursively search
                if ($this->backtrack($timetable)) {
                    return true;
                }

                // Backtrack
                unset($this->assignment[$variable['id']]);
                $this->stats['backtracks']++;
            }
        }

        return false;
    }

    /**
     * Select unassigned variable using MRV heuristic
     * (Choose variable with smallest domain)
     */
    private function selectUnassignedVariable()
    {
        $minDomainSize = PHP_INT_MAX;
        $selectedVariable = null;

        foreach ($this->variables as $variable) {
            if (!isset($this->assignment[$variable['id']])) {
                $domainSize = count($this->domains[$variable['id']] ?? []);

                if ($domainSize === 0) {
                    return null; // No valid values - early failure
                }

                if ($domainSize < $minDomainSize) {
                    $minDomainSize = $domainSize;
                    $selectedVariable = $variable;
                }
            }
        }

        return $selectedVariable;
    }

    /**
     * Order domain values using LCV heuristic
     * (Prefer values that leave most options for neighbors)
     */
    private function orderDomainValues($variable): array
    {
        $domain = $this->domains[$variable['id']] ?? [];

        // For simplicity, sort by constraints: prefer earlier slots, preferred teachers
        usort($domain, function($a, $b) {
            // Prefer Monday-Friday over Saturday
            $daysOrder = ['Monday' => 0, 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5];
            $dayDiff = ($daysOrder[$a['day_of_week']] ?? 6) - ($daysOrder[$b['day_of_week']] ?? 6);
            if ($dayDiff !== 0) return $dayDiff;

            // Prefer earlier time slots
            return ($a['time_slot_id'] ?? 0) - ($b['time_slot_id'] ?? 0);
        });

        return $domain;
    }

    /**
     * Check if an assignment is consistent
     */
    private function isConsistentAssignment($variable, $value): bool
    {
        // Check hard constraints against existing assignments
        $assignment = array_merge($value, [
            'class_id' => $variable['class_id'],
            'subject_id' => $variable['subject_id'],
        ]);

        $violations = $this->validator->validateHardConstraints($assignment);
        return empty($violations);
    }

    /**
     * Save assignments to database
     */
    private function saveAssignments($timetable): void
    {
        foreach ($this->assignment as $variableId => $value) {
            // Extract variable info
            $parts = explode('_', $variableId);
            $classId = $parts[0];
            $subjectId = $parts[1];

            TimetableEntry::create([
                'timetable_id' => $timetable->id,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'teacher_id' => $value['teacher_id'],
                'room_id' => $value['room_id'],
                'time_slot_id' => $value['time_slot_id'],
                'day_of_week' => $value['day_of_week'],
            ]);
        }

        // Update timetable status
        $timetable->update([
            'status' => 'generated',
            'conflict_count' => 0,
        ]);
    }

    /**
     * Get solving statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
