export interface User {
    id: number;
    name: string;
    email: string;
    role: Role;
    role_label: string;
    avatar: string | null;
}

export type Role =
    | 'admin'
    | 'principal'
    | 'teacher'
    | 'receptionist'
    | 'principal_helper'
    | 'inventory_manager'
    | 'doctor';

export interface PageProps<T extends Record<string, unknown> = Record<string, unknown>> {
    auth: { user: User | null };
    flash: { success?: string; error?: string; warning?: string };
    school: { name: string; url: string };
    unread_notices_count: number;
    errors: Partial<Record<string, string>>;
    [key: string]: unknown;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    next_page_url: string | null;
    prev_page_url: string | null;
}

export interface SchoolClass {
    id: number;
    class: string;
    section: string | null;
    academic_year: string;
    is_active: boolean;
    full_name?: string;
    students_count?: number;
    name?: string;
}

export interface Student {
    id: number;
    admission_no: string;
    full_name: string;
    student_cnic: string | null;
    dob: string;
    gender: 'male' | 'female' | 'other';
    class_id: number;
    class?: SchoolClass;
    father_name: string | null;
    father_cnic: string | null;
    mother_name: string | null;
    mother_cnic: string | null;
    guardian_name: string | null;
    guardian_phone: string | null;
    guardian_cnic: string | null;
    guardian_address: string | null;
    blood_group: string | null;
    favorite_color: string | null;
    favorite_food: string | null;
    favorite_subject: string | null;
    ambition: string | null;
    is_orphan: boolean;
    trust_notes: string | null;
    join_date_kort: string | null;
    group_stream: string;
    semester: string | null;
    phone: string | null;
    email: string | null;
    previous_school: string | null;
    photo: string | null;
    is_active: boolean;
    reason_left_kort: string | null;
    leaving_date: string | null;
    subject_group_id: number | null;
    subject_group?: SubjectGroup;
    attendance?: Attendance[];
    results?: Result[];
    discipline_records?: DisciplineRecord[];
    behaviour_records?: BehaviourRecord[];
    documents?: StudentDocument[];
    created_at: string;
}

export interface TeacherProfile {
    id: number;
    user_id: number;
    employee_id: string;
    phone: string | null;
    qualification: string | null;
    specialisation: string | null;
    date_joined: string | null;
    is_active: boolean;
    primary_subject?: string;
    class_teacher_classes?: string[];
    user?: User;
    assignments?: TeacherAssignment[];
    lesson_plans?: LessonPlan[];
    leave_requests?: LeaveRequest[];
}

export interface TeacherAssignment {
    id: number;
    teacher_id: number;
    class_id: number;
    subject_id: number;
    academic_year: string;
    assignment_type: 'class_teacher' | 'subject_teacher';
    teacherProfile?: TeacherProfile;
    teacher?: User;
    class?: SchoolClass;
    subject?: Subject;
    class_teacher_name?: string;
}

export interface Subject {
    id: number;
    subject_name: string;
    subject_code: string | null;
    is_active: boolean;
    name?: string;
    code?: string | null;
}

export interface SubjectGroup {
    id: number;
    group_name: string;
    group_slug: string;
    stream: string | null;
    description: string | null;
    is_active: boolean;
    name?: string;
}

export interface Attendance {
    id: number;
    student_id: number;
    class_id: number;
    subject_id: number;
    attendance_date: string;
    status: 'P' | 'A' | 'L';
    marked_by: number;
    remarks: string | null;
    student?: Student;
    class?: SchoolClass;
}

export interface Result {
    id: number;
    student_id: number;
    class_id: number;
    subject_id: number;
    teacher_id: number;
    exam_type: string;
    academic_year: string;
    term: string;
    total_marks: number;
    obtained_marks: number;
    percentage: number;
    grade: string;
    gpa_point: number;
    approval_status: 'pending' | 'class_teacher_approved' | 'approved' | 'rejected';
    is_locked: boolean;
    approved_by: number | null;
    approved_at: string | null;
    class_teacher_reviewed_by?: number | null;
    class_teacher_reviewed_at?: string | null;
    class_teacher_remarks?: string | null;
    principal_remarks?: string | null;
    rejection_reason?: string | null;
    student?: Student;
    class?: SchoolClass & { class_teacher_id?: number | null };
    subject?: Subject;
    teacher?: User;
}

export interface StudentReportCard {
    student: {
        id: number;
        full_name: string;
        admission_no: string;
        father_name: string | null;
        photo: string | null;
        stream: string | null;
        class: {
            class: string;
            section: string | null;
            full_name: string;
        } | null;
    };
    results: Array<{
        subject_name: string;
        obtained_marks: number;
        total_marks: number;
        percentage: number;
        grade: string;
        gpa_point: number;
        class_teacher_remarks?: string | null;
        principal_remarks?: string | null;
    }>;
    summary: {
        total_obtained: number;
        total_possible: number;
        overall_percentage: number;
        overall_grade: string;
        average_gpa: number;
        pass_fail: 'PASS' | 'FAIL';
    };
}

export interface LeaveRequest {
    id: number;
    request_type: 'teacher' | 'student';
    teacher_id: number | null;
    student_id: number | null;
    class_id?: number | null;
    from_date: string;
    to_date: string;
    leave_type: 'casual' | 'annual' | 'emergency' | 'other' | null;
    other_leave_type: string | null;
    reason: string;
    status: 'Pending' | 'Approved' | 'Rejected';
    approved_by: number | null;
    approved_at: string | null;
    remarks: string | null;
    teacher?: User;
    student?: Student;
    days?: number;
    created_at: string;
}

export interface LessonPlan {
    id: number;
    teacher_profile_id: number;
    class_id: number;
    subject_id: number;
    week_starting: string;
    topic: string;
    objectives: string;
    resources: string | null;
    activities: string | null;
    homework: string | null;
    status: 'pending' | 'approved' | 'rejected';
    principal_feedback: string | null;
    reviewed_by: number | null;
    reviewed_at: string | null;
    teacher?: TeacherProfile;
    class?: SchoolClass;
    subject?: Subject;
    reviewedBy?: User;
}

export interface DisciplineRecord {
    id: number;
    student_id: number;
    class_id: number;
    category: 'warning' | 'achievement' | 'suspension' | 'other';
    severity: 'low' | 'medium' | 'high';
    incident_date: string;
    title: string;
    description: string | null;
    status: 'open' | 'resolved' | 'escalated';
    recorded_by: number;
    report_to_principal: boolean;
    student?: Student;
    recorded_by_user?: User;
}

export interface BehaviourRecord {
    id: number;
    student_id: number;
    type: string;
    description: string;
    recorded_by: number;
    created_at: string;
    recorded_by_user?: User;
}

export interface TeacherReport {
    id: number;
    subject_teacher_id: number;
    class_teacher_id: number;
    class_id: number;
    report_type: 'general' | 'performance' | 'conduct' | 'attendance';
    notes: string;
    status: 'open' | 'resolved' | 'closed';
    resolved_at: string | null;
    created_at: string;
    updated_at: string;
    subjectTeacher?: User;
    classTeacher?: User;
    class?: SchoolClass;
}

export interface StudentDocument {
    id: number;
    student_id: number;
    title: string;
    file_path: string;
    uploaded_by: number;
    created_at: string;
}

export interface DisciplineAction {
    id: number;
    discipline_record_id: number;
    action: string;
    taken_by: number;
    taken_at: string;
    notes: string | null;
}

export interface SickRecord {
    id: number;
    student_id: number;
    doctor_id: number;
    symptoms: string;
    diagnosis: string | null;
    treatment: string | null;
    referred_to_hospital: boolean;
    visit_date: string;
    notes: string | null;
    student?: Student;
    doctor?: User;
}

export interface Notice {
    id: number;
    title: string;
    body: string;
    target_scope: 'all' | 'role' | 'teacher' | 'class';
    target_role: string | null;
    target_user_id: number | null;
    target_class_id: number | null;
    posted_by: number;
    expires_at: string | null;
    is_active: boolean;
    postedBy?: User;
    created_at: string;
    updated_at: string;
}

export interface InboxMessage {
    id: number;
    from_user_id: number;
    to_user_id: number;
    subject: string;
    body: string;
    is_read: boolean;
    read_at: string | null;
    from?: User;
    to?: User;
    created_at: string;
}

export interface InventoryItem {
    id: number;
    category_id: number;
    name: string;
    description: string | null;
    unit: string;
    current_stock: number;
    min_stock_level: number;
    is_active: boolean;
    category?: InventoryCategory;
}

export interface InventoryCategory {
    id: number;
    name: string;
    description: string | null;
    items_count?: number;
}

export interface InventoryLedger {
    id: number;
    item_id: number;
    transaction_type: 'in' | 'out';
    quantity: number;
    balance_after: number;
    notes: string | null;
    created_at: string;
    item?: InventoryItem;
    createdBy?: User;
}

export interface AuditLog {
    id: number;
    user_id: number;
    action: string;
    resource: string;
    resource_id: number | null;
    ip_address: string | null;
    created_at: string;
    user?: User;
}

export interface StudentDatesheet {
    id: number;
    class_name: string;
    subject_name: string;
    exam_date: string;
    exam_time?: string;
    room_no?: string;
    total_marks: number;
    exam_period?: string;
    academic_year?: string;
    created_at?: string;
    updated_at?: string;
}

export interface AttendanceCriteria {
    id: number;
    class_id?: number;
    subject_id?: number;
    criteria_type: 'class' | 'subject';
    min_attendance_percent: number;
    max_allowed_absences?: number;
    academic_year: string;
    created_by: number;
    updated_by?: number;
    class?: SchoolClass;
    subject?: Subject;
    created_by_user?: User;
    updated_by_user?: User;
    created_at?: string;
    updated_at?: string;
}

export interface AdmissionCard {
    id: number;
    student_id: number;
    class_id: number;
    academic_year: string;
    exam_period: string;
    attendance_eligible: boolean;
    attendance_percent?: number;
    status: 'draft' | 'issued';
    issued_date?: string;
    approved_by?: number;
    generated_by: number;
    pdf_path?: string;
    student?: Student;
    class?: SchoolClass;
    generated_by_user?: User;
    approved_by_user?: User;
    created_at?: string;
    updated_at?: string;
}

export interface TeacherDevice {
    id: number;
    teacher_id: number;
    device_type: 'laptop' | 'chromebook' | 'tablet';
    serial_number: string;
    model: string;
    made_year: number;
    assigned_at: string;
    unassigned_at?: string | null;
    notes?: string | null;
    teacher?: User;
    created_at?: string;
    updated_at?: string;
}
