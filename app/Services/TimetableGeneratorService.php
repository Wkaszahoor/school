<?php

namespace App\Services;

use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\TimetableConflict;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\RoomConfiguration;
use App\Models\TeacherAvailability;
use Illuminate\Support\Facades\DB;

/**
 * Main orchestrator for timetable generation
 * Coordinates constraint validation, propagation, and CSP solving
 */
class TimetableGeneratorService
{
    private ConstraintValidator $validator;
    private CSPEngine $cspEngine;
    private ConflictDetector $conflictDetector;

    public function __construct(
        ConstraintValidator $validator,
        CSPEngine $cspEngine,
        ConflictDetector $conflictDetector
    ) {
        $this->validator = $validator;
        $this->cspEngine = $cspEngine;
        $this->conflictDetector = $conflictDetector;
    }

    /**
     * Generate timetable for given timetable ID
     */
    public function generate($timetableId): array
    {
        try {
            DB::beginTransaction();

            $timetable = Timetable::find($timetableId);
            if (!$timetable) {
                throw new \Exception('Timetable not found');
            }

            // Update status
            $timetable->update(['status' => 'generating']);

            // Validate input data
            $validation = $this->validateInputData($timetable);
            if (!$validation['success']) {
                DB::rollBack();
                return $validation;
            }

            // Run CSP solver
            $result = $this->cspEngine->solve($timetableId);

            if ($result['success']) {
                // Detect soft constraint violations
                $conflicts = $this->detectConflicts($timetable);
                $timetable->update([
                    'status' => 'generated',
                    'conflict_count' => count($conflicts),
                ]);

                DB::commit();
                return [
                    'success' => true,
                    'message' => 'Timetable generated successfully',
                    'entries' => TimetableEntry::where('timetable_id', $timetableId)->count(),
                    'conflicts' => count($conflicts),
                    'stats' => $result['stats'] ?? []
                ];
            } else {
                DB::rollBack();
                return $result;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Generation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate input data before generation
     */
    private function validateInputData($timetable): array
    {
        // Check if there are classes assigned
        $classes = SchoolClass::count();
        if ($classes === 0) {
            return ['success' => false, 'error' => 'No classes found in system'];
        }

        // Check if there are teachers
        $teachers = \App\Models\User::where('role', 'teacher')->count();
        if ($teachers === 0) {
            return ['success' => false, 'error' => 'No teachers found in system'];
        }

        // Check if there are time slots
        $slots = TimeSlot::active()->count();
        if ($slots === 0) {
            return ['success' => false, 'error' => 'No time slots configured'];
        }

        // Check if there are rooms
        $rooms = RoomConfiguration::active()->count();
        if ($rooms === 0) {
            return ['success' => false, 'error' => 'No rooms configured'];
        }

        return ['success' => true];
    }

    /**
     * Detect conflicts in generated timetable
     */
    private function detectConflicts($timetable): array
    {
        $conflicts = [];
        $entries = TimetableEntry::where('timetable_id', $timetable->id)->get();
        $entriesArray = $entries->toArray();

        foreach ($entries as $entry) {
            $softViolations = $this->conflictDetector->detectSoftViolations(
                $entry->toArray(),
                $entriesArray
            );

            foreach ($softViolations as $violation) {
                TimetableConflict::create([
                    'timetable_id' => $timetable->id,
                    'entry_id' => $entry->id,
                    'conflict_type' => $violation['type'],
                    'severity' => $violation['severity'],
                    'description' => $violation['message'],
                    'affected_entries' => json_encode([$entry->id]),
                ]);

                $conflicts[] = $violation;
            }
        }

        return $conflicts;
    }

    /**
     * Update an entry (drag-drop rescheduling)
     */
    public function updateEntry($entryId, $data): array
    {
        try {
            $entry = TimetableEntry::find($entryId);
            if (!$entry) {
                return ['success' => false, 'error' => 'Entry not found'];
            }

            // Build assignment
            $assignment = [
                'id' => $entryId,
                'timetable_id' => $entry->timetable_id,
                'class_id' => $entry->class_id,
                'subject_id' => $entry->subject_id,
                'teacher_id' => $data['teacher_id'] ?? $entry->teacher_id,
                'room_id' => $data['room_id'] ?? $entry->room_id,
                'time_slot_id' => $data['time_slot_id'] ?? $entry->time_slot_id,
                'day_of_week' => $data['day_of_week'] ?? $entry->day_of_week,
            ];

            // Validate hard constraints
            $violations = $this->validator->validateHardConstraints($assignment);
            if (!empty($violations)) {
                return [
                    'success' => false,
                    'error' => 'Assignment violates constraints: ' . implode(', ', $violations)
                ];
            }

            // Update entry
            $entry->update($assignment);

            // Redetect soft conflicts for this timetable
            $this->refreshTimetableConflicts($entry->timetable_id);

            return ['success' => true, 'message' => 'Entry updated successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Refresh conflict detection for a timetable
     */
    private function refreshTimetableConflicts($timetableId): void
    {
        // Clear old conflicts
        TimetableConflict::where('timetable_id', $timetableId)->delete();

        // Redetect
        $timetable = Timetable::find($timetableId);
        $conflicts = $this->detectConflicts($timetable);

        $timetable->update(['conflict_count' => count($conflicts)]);
    }

    /**
     * Get timetable entries grouped by class
     */
    public function getByClass($timetableId, $classId): array
    {
        return TimetableEntry::where('timetable_id', $timetableId)
            ->where('class_id', $classId)
            ->with('teacher', 'subject', 'room', 'timeSlot')
            ->orderBy('day_of_week')
            ->orderBy('time_slot_id')
            ->get()
            ->toArray();
    }

    /**
     * Get timetable entries grouped by teacher
     */
    public function getByTeacher($timetableId, $teacherId): array
    {
        return TimetableEntry::where('timetable_id', $timetableId)
            ->where('teacher_id', $teacherId)
            ->with('schoolClass', 'subject', 'room', 'timeSlot')
            ->orderBy('day_of_week')
            ->orderBy('time_slot_id')
            ->get()
            ->toArray();
    }

    /**
     * Get timetable entries grouped by room
     */
    public function getByRoom($timetableId, $roomId): array
    {
        return TimetableEntry::where('timetable_id', $timetableId)
            ->where('room_id', $roomId)
            ->with('schoolClass', 'subject', 'teacher', 'timeSlot')
            ->orderBy('day_of_week')
            ->orderBy('time_slot_id')
            ->get()
            ->toArray();
    }

    /**
     * Publish timetable
     */
    public function publish($timetableId): array
    {
        try {
            $timetable = Timetable::find($timetableId);
            if (!$timetable) {
                return ['success' => false, 'error' => 'Timetable not found'];
            }

            // Check for unresolved hard conflicts
            $hardConflicts = TimetableConflict::where('timetable_id', $timetableId)
                ->where('severity', 'hard')
                ->where('is_resolved', false)
                ->count();

            if ($hardConflicts > 0) {
                return [
                    'success' => false,
                    'error' => "Cannot publish: $hardConflicts unresolved hard conflicts"
                ];
            }

            $timetable->publish();

            return ['success' => true, 'message' => 'Timetable published successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
