<?php

namespace App\Services;

use App\Models\TimetableEntry;
use App\Models\TeacherAvailability;
use App\Models\Timetable;

/**
 * Validates hard and soft constraints for timetable generation
 */
class ConstraintValidator
{
    /**
     * Check if an assignment violates hard constraints
     */
    public function hasHardConstraintViolation($assignment): bool
    {
        return $this->hasTeacherDoubleBooking($assignment) ||
               $this->hasRoomDoubleBooking($assignment) ||
               $this->violatesTeacherAvailability($assignment);
    }

    /**
     * Check if teacher is double-booked
     */
    public function hasTeacherDoubleBooking($assignment): bool
    {
        $conflicting = TimetableEntry::where('timetable_id', $assignment['timetable_id'])
            ->where('teacher_id', $assignment['teacher_id'])
            ->where('day_of_week', $assignment['day_of_week'])
            ->where('time_slot_id', $assignment['time_slot_id'])
            ->where('id', '!=', $assignment['id'] ?? 0)
            ->exists();

        return $conflicting;
    }

    /**
     * Check if room is double-booked
     */
    public function hasRoomDoubleBooking($assignment): bool
    {
        $conflicting = TimetableEntry::where('timetable_id', $assignment['timetable_id'])
            ->where('room_id', $assignment['room_id'])
            ->where('day_of_week', $assignment['day_of_week'])
            ->where('time_slot_id', $assignment['time_slot_id'])
            ->where('id', '!=', $assignment['id'] ?? 0)
            ->exists();

        return $conflicting;
    }

    /**
     * Check if assignment violates teacher availability constraints
     */
    public function violatesTeacherAvailability($assignment): bool
    {
        // Check if teacher has unavailable marking for this day/time
        $unavailable = TeacherAvailability::where('teacher_id', $assignment['teacher_id'])
            ->where('day_of_week', $assignment['day_of_week'])
            ->where('time_slot_id', $assignment['time_slot_id'])
            ->where('availability_type', 'unavailable')
            ->where('is_active', true)
            ->exists();

        return $unavailable;
    }

    /**
     * Check if class is double-booked
     */
    public function hasClassDoubleBooking($assignment): bool
    {
        $conflicting = TimetableEntry::where('timetable_id', $assignment['timetable_id'])
            ->where('class_id', $assignment['class_id'])
            ->where('day_of_week', $assignment['day_of_week'])
            ->where('time_slot_id', $assignment['time_slot_id'])
            ->where('id', '!=', $assignment['id'] ?? 0)
            ->exists();

        return $conflicting;
    }

    /**
     * Validate all hard constraints for an assignment
     */
    public function validateHardConstraints($assignment): array
    {
        $violations = [];

        if ($this->hasTeacherDoubleBooking($assignment)) {
            $violations[] = 'teacher_double_booking';
        }

        if ($this->hasRoomDoubleBooking($assignment)) {
            $violations[] = 'room_double_booking';
        }

        if ($this->violatesTeacherAvailability($assignment)) {
            $violations[] = 'teacher_availability';
        }

        if ($this->hasClassDoubleBooking($assignment)) {
            $violations[] = 'class_double_booking';
        }

        return $violations;
    }
}
