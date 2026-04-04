<?php

namespace App\Console\Commands;

use App\Models\{Timetable, TimetableEntry, TimeSlot, RoomConfiguration, SchoolClass, Subject, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTimetableTestData extends Command
{
    protected $signature = 'timetable:seed-test';
    protected $description = 'Seed time slots, rooms, and a test timetable with entries';

    public function handle()
    {
        $this->info('Seeding timetable test data...');

        DB::transaction(function () {
            $this->seedTimeSlots();
            $this->seedRooms();
            $this->createTestTimetable();
        });

        $this->info('Done!');
    }

    private function seedTimeSlots()
    {
        $this->line('Creating time slots...');
        TimeSlot::query()->delete();

        $slots = [
            ['period_number' => 1, 'name' => 'Period 1', 'start_time' => '08:00', 'end_time' => '08:45', 'slot_type' => 'regular'],
            ['period_number' => 2, 'name' => 'Period 2', 'start_time' => '08:45', 'end_time' => '09:30', 'slot_type' => 'regular'],
            ['period_number' => 3, 'name' => 'Period 3', 'start_time' => '09:30', 'end_time' => '10:15', 'slot_type' => 'regular'],
            ['period_number' => 98, 'name' => 'Break',    'start_time' => '10:15', 'end_time' => '10:30', 'slot_type' => 'break'],
            ['period_number' => 4, 'name' => 'Period 4', 'start_time' => '10:30', 'end_time' => '11:15', 'slot_type' => 'regular'],
            ['period_number' => 5, 'name' => 'Period 5', 'start_time' => '11:15', 'end_time' => '12:00', 'slot_type' => 'regular'],
            ['period_number' => 99, 'name' => 'Lunch',    'start_time' => '12:00', 'end_time' => '12:30', 'slot_type' => 'lunch'],
            ['period_number' => 6, 'name' => 'Period 6', 'start_time' => '12:30', 'end_time' => '13:15', 'slot_type' => 'regular'],
            ['period_number' => 7, 'name' => 'Period 7', 'start_time' => '13:15', 'end_time' => '14:00', 'slot_type' => 'regular'],
        ];

        foreach ($slots as $slot) {
            [$h1, $m1] = explode(':', $slot['start_time']);
            [$h2, $m2] = explode(':', $slot['end_time']);
            $duration = ($h2 * 60 + $m2) - ($h1 * 60 + $m1);

            TimeSlot::create([
                'name'             => $slot['name'],
                'start_time'       => $slot['start_time'],
                'end_time'         => $slot['end_time'],
                'duration_minutes' => $duration,
                'period_number'    => $slot['period_number'],
                'slot_type'        => $slot['slot_type'],
                'is_active'        => true,
            ]);
        }

        $this->line('  ✓ ' . count($slots) . ' time slots created');
    }

    private function seedRooms()
    {
        $this->line('Creating rooms...');
        RoomConfiguration::query()->delete();

        $rooms = [
            ['room_name' => 'Room 101', 'room_type' => 'classroom', 'capacity' => 35, 'block' => 'A', 'floor' => '1'],
            ['room_name' => 'Room 102', 'room_type' => 'classroom', 'capacity' => 35, 'block' => 'A', 'floor' => '1'],
            ['room_name' => 'Room 201', 'room_type' => 'classroom', 'capacity' => 35, 'block' => 'B', 'floor' => '2'],
            ['room_name' => 'Room 202', 'room_type' => 'classroom', 'capacity' => 35, 'block' => 'B', 'floor' => '2'],
            ['room_name' => 'Science Lab', 'room_type' => 'lab',       'capacity' => 25, 'block' => 'C', 'floor' => '1'],
            ['room_name' => 'Computer Lab', 'room_type' => 'lab',      'capacity' => 25, 'block' => 'C', 'floor' => '1'],
        ];

        foreach ($rooms as $room) {
            RoomConfiguration::create(array_merge($room, ['is_active' => true]));
        }

        $this->line('  ✓ ' . count($rooms) . ' rooms created');
    }

    private function createTestTimetable()
    {
        $this->line('Creating test timetable...');

        // Reset stuck timetable
        Timetable::where('status', 'generating')->update(['status' => 'draft']);

        // Delete old test timetable entries
        Timetable::where('name', 'Test Timetable 2026')->delete();

        $admin = User::where('role', 'admin')->orWhere('role', 'principal')->first();
        $timetable = Timetable::create([
            'name'          => 'Test Timetable 2026',
            'created_by'    => $admin->id,
            'academic_year' => '2025-2026',
            'term'          => 'spring',
            'status'        => 'draft',
            'start_date'    => '2026-01-01',
            'end_date'      => '2026-05-31',
            'total_days'    => 5,
            'notes'         => 'Test timetable for demo',
        ]);

        // Get data
        $classes   = SchoolClass::whereIn('class', ['9', '10'])->limit(3)->get();
        $subjects  = Subject::whereIn('subject_name', ['Mathematics', 'English', 'Science', 'History', 'Geography'])->limit(5)->get();
        $teachers  = User::where('role', 'teacher')->limit(5)->get();
        $rooms     = RoomConfiguration::where('room_type', 'classroom')->limit(3)->get();
        $slots     = TimeSlot::where('slot_type', 'regular')->orderBy('period_number')->get();
        $days      = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        if ($classes->isEmpty() || $subjects->isEmpty() || $teachers->isEmpty() || $rooms->isEmpty() || $slots->isEmpty()) {
            $this->warn('  ⚠ Not enough data to create entries (need classes, subjects, teachers, rooms)');
            return;
        }

        $entries = 0;
        foreach ($days as $day) {
            foreach ($slots->take(5) as $slotIdx => $slot) {
                foreach ($classes->take(2) as $classIdx => $class) {
                    $subject = $subjects->get($slotIdx % $subjects->count());
                    $teacher = $teachers->get(($slotIdx + $classIdx) % $teachers->count());
                    $room    = $rooms->get($classIdx % $rooms->count());

                    // Skip if this class+slot+day already has an entry
                    $exists = TimetableEntry::where([
                        'timetable_id' => $timetable->id,
                        'class_id'     => $class->id,
                        'time_slot_id' => $slot->id,
                        'day_of_week'  => $day,
                    ])->exists();

                    if (!$exists) {
                        TimetableEntry::create([
                            'timetable_id' => $timetable->id,
                            'class_id'     => $class->id,
                            'subject_id'   => $subject->id,
                            'teacher_id'   => $teacher->id,
                            'room_id'      => $room->id,
                            'time_slot_id' => $slot->id,
                            'day_of_week'  => $day,
                            'is_locked'    => false,
                        ]);
                        $entries++;
                    }
                }
            }
        }

        $timetable->update([
            'status'            => 'generated',
            'total_classes'     => $classes->count(),
            'total_teachers'    => $teachers->count(),
            'total_rooms'       => $rooms->count(),
            'total_time_slots'  => $slots->count(),
        ]);

        $this->line("  ✓ Timetable '{$timetable->name}' created with {$entries} entries");
        $this->line("  → http://127.0.0.1:8000/principal/timetables/{$timetable->id}");
    }
}
