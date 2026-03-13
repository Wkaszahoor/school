export interface TrainingCourse {
  id: number;
  title: string;
  description: string;
  duration_hours: number;
  level: 'beginner' | 'intermediate' | 'advanced';
  status: 'draft' | 'published' | 'archived';
  course_category_id?: number;
  created_by_id: number;
  created_by?: {
    id: number;
    name: string;
  };
  created_at: string;
  updated_at: string;
  materials_count?: number;
}

export interface CourseEnrollment {
  id: number;
  training_course_id: number;
  teacher_id: number;
  teacher?: {
    id: number;
    name: string;
    email: string;
  };
  enrolled_at: string;
  completion_date?: string;
  status: 'enrolled' | 'in_progress' | 'completed' | 'dropped';
  progress_percentage: number;
  course?: TrainingCourse;
}

export interface CourseMaterial {
  id: number;
  training_course_id: number;
  file_name: string;
  file_path: string;
  file_size: number;
  mime_type: string;
  uploaded_at: string;
}

export interface PBLAssignment {
  id: number;
  teacher_id: number;
  class_id: number;
  class?: {
    id: number;
    name: string;
  };
  teacher?: {
    id: number;
    name: string;
  };
  title: string;
  description: string;
  start_date: string;
  due_date: string;
  status: 'draft' | 'active' | 'completed' | 'archived';
  rubric_id?: number;
  academic_year: string;
  student_groups_count?: number;
  submissions_count?: number;
}

export interface StudentGroup {
  id: number;
  pbl_assignment_id: number;
  group_name: string;
  group_leader_id?: number;
  group_leader?: {
    id: number;
    name: string;
  };
  members_count?: number;
  submission_status?: 'draft' | 'submitted' | 'graded' | 'revision_requested';
}

export interface GroupSubmission {
  id: number;
  student_group_id: number;
  submitted_at?: string;
  file_name?: string;
  file_path?: string;
  status: 'draft' | 'submitted' | 'graded' | 'revision_requested';
  score?: number;
  feedback?: string;
  group?: StudentGroup;
}

export interface RubricCriterion {
  id: number;
  pbl_rubric_id: number;
  criterion_name: string;
  description: string;
  max_points: number;
  order: number;
}

export interface PBLRubric {
  id: number;
  pbl_assignment_id: number;
  criteria: RubricCriterion[];
}

export interface Certification {
  id: number;
  teacher_id: number;
  teacher?: {
    id: number;
    name: string;
  };
  certification_type: 'course_completion' | 'professional_development_hours' | 'skill_mastery' | 'custom';
  title: string;
  issued_date: string;
  expiry_date?: string;
  certificate_number: string;
  status: 'active' | 'expired' | 'revoked';
  issuing_body: string;
  description: string;
  pdf_path?: string;
  created_at: string;
  updated_at: string;
}

export interface TeachingResource {
  id: number;
  title: string;
  description: string;
  resource_type: 'pdf' | 'video' | 'document' | 'url' | 'image';
  file_path?: string;
  external_url?: string;
  subject_id?: number;
  subject?: {
    id: number;
    name: string;
  };
  topic_category: string;
  uploaded_by_id: number;
  uploaded_by?: {
    id: number;
    name: string;
  };
  status: 'draft' | 'published' | 'archived';
  download_count: number;
  last_downloaded_at?: string;
  created_at: string;
  updated_at: string;
}

export interface PageProps {
  auth?: {
    user: {
      id: number;
      name: string;
      email: string;
      role: string;
    };
  };
  flash?: {
    message?: string;
    error?: string;
    success?: string;
  };
  errors?: Record<string, string>;
}
