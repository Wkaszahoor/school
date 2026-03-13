<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\RoomConfiguration;
use App\Models\TimeSlot;
use Inertia\Inertia;

class TimetableViewController extends Controller
{
    public function byClass(Timetable $timetable, SchoolClass $class)
    {
        $timeSlots = TimeSlot::active()->orderBy('period_number')->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        $entries = $timetable->entries()->where('class_id', $class->id)
            ->with('subject', 'teacher', 'room', 'timeSlot')
            ->get();

        $schedule = [];
        foreach ($days as $day) {
            $schedule[$day] = [];
            foreach ($timeSlots as $slot) {
                $entry = $entries->firstWhere(fn($e) => $e->day_of_week === $day && $e->time_slot_id === $slot->id);
                $schedule[$day][$slot->id] = $entry;
            }
        }

        return Inertia::render('Principal/Timetables/ByClassView', compact('timetable', 'class', 'schedule', 'timeSlots', 'days'));
    }

    public function byTeacher(Timetable $timetable, User $teacher)
    {
        $timeSlots = TimeSlot::active()->orderBy('period_number')->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        $entries = $timetable->entries()->where('teacher_id', $teacher->id)
            ->with('schoolClass', 'subject', 'room', 'timeSlot')
            ->get();

        $schedule = [];
        foreach ($days as $day) {
            $schedule[$day] = [];
            foreach ($timeSlots as $slot) {
                $entry = $entries->firstWhere(fn($e) => $e->day_of_week === $day && $e->time_slot_id === $slot->id);
                $schedule[$day][$slot->id] = $entry;
            }
        }

        return Inertia::render('Principal/Timetables/ByTeacherView', compact('timetable', 'teacher', 'schedule', 'timeSlots', 'days'));
    }

    public function byRoom(Timetable $timetable, RoomConfiguration $room)
    {
        $timeSlots = TimeSlot::active()->orderBy('period_number')->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        $entries = $timetable->entries()->where('room_id', $room->id)
            ->with('schoolClass', 'subject', 'teacher', 'timeSlot')
            ->get();

        $schedule = [];
        foreach ($days as $day) {
            $schedule[$day] = [];
            foreach ($timeSlots as $slot) {
                $entry = $entries->firstWhere(fn($e) => $e->day_of_week === $day && $e->time_slot_id === $slot->id);
                $schedule[$day][$slot->id] = $entry;
            }
        }

        return Inertia::render('Principal/Timetables/ByRoomView', compact('timetable', 'room', 'schedule', 'timeSlots', 'days'));
    }
}
