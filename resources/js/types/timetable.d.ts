export interface TimeSlot {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    duration_minutes: number;
    period_number: number;
    slot_type: 'regular' | 'break' | 'lunch' | 'assembly';
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface RoomConfiguration {
    id: number;
    room_name: string;
    room_type: 'classroom' | 'lab' | 'auditorium' | 'sports' | 'art' | 'music' | 'library';
    capacity: number;
    block?: string;
    floor?: string;
    has_projector: boolean;
    has_lab_equipment: boolean;
    has_ac: boolean;
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface TeacherAvailability {
    id: number;
    teacher_id: number;
    day_of_week: 'Monday' | 'Tuesday' | 'Wednesday' | 'Thursday' | 'Friday' | 'Saturday';
    time_slot_id?: number;
    availability_type: 'available' | 'unavailable' | 'preferred';
    notes?: string;
    max_periods_per_day?: number;
    min_free_periods: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    teacher?: {
        id: number;
        name: string;
        email: string;
    };
    timeSlot?: TimeSlot;
}

export interface TimetableEntry {
    id: number;
    timetable_id: number;
    class_id: number;
    subject_id: number;
    teacher_id: number;
    room_id: number;
    time_slot_id: number;
    day_of_week: 'Monday' | 'Tuesday' | 'Wednesday' | 'Thursday' | 'Friday' | 'Saturday';
    is_locked: boolean;
    notes?: string;
    created_at: string;
    updated_at: string;
    schoolClass?: {
        id: number;
        name: string;
    };
    subject?: {
        id: number;
        name: string;
    };
    teacher?: {
        id: number;
        name: string;
        email: string;
    };
    room?: RoomConfiguration;
    timeSlot?: TimeSlot;
}

export interface TimetableConflict {
    id: number;
    timetable_id: number;
    entry_id?: number;
    conflict_type:
        | 'teacher_double_booking'
        | 'room_double_booking'
        | 'teacher_availability'
        | 'room_unavailable'
        | 'consecutive_classes'
        | 'free_period_violation'
        | 'unbalanced_workload';
    severity: 'hard' | 'soft';
    description: string;
    affected_entries?: number[];
    is_resolved: boolean;
    resolution_notes?: string;
    created_at: string;
    updated_at: string;
    entry?: TimetableEntry;
}

export interface Timetable {
    id: number;
    name: string;
    created_by: number;
    academic_year: string;
    term: 'spring' | 'summer' | 'autumn';
    status: 'draft' | 'generating' | 'generated' | 'published' | 'archived';
    start_date: string;
    end_date: string;
    total_classes: number;
    total_teachers: number;
    total_rooms: number;
    total_time_slots: number;
    total_days: number;
    generation_config?: Record<string, unknown>;
    notes?: string;
    conflict_count: number;
    published_at?: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
    creator?: {
        id: number;
        name: string;
        email: string;
    };
    entries?: TimetableEntry[];
    conflicts?: TimetableConflict[];
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<{
        url?: string;
        label: string;
        active: boolean;
    }>;
    next_page_url?: string;
    path: string;
    per_page: number;
    prev_page_url?: string;
    to: number;
    total: number;
}

export interface ScheduleGrid {
    [day: string]: {
        [timeSlotId: number]: TimetableEntry | undefined;
    };
}
