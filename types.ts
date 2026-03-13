
export type UserRole = 'admin' | 'teacher';

export enum LeaveStatus {
  Pending = 'Pending',
  Approved = 'Approved',
  Rejected = 'Rejected',
}

export enum AttendanceStatus {
  Present = 'Present',
  Absent = 'Absent',
  Late = 'Late',
}

export enum ResultType {
  Weekly = 'Weekly',
  Monthly = 'Monthly',
  Term = '1st Term',
}

export interface Student {
  id: number;
  name: string;
  classId: number;
  rollNumber: string;
}

export interface Teacher {
  id: number;
  name: string;
  assignedClasses: number[];
  assignedSubjects: number[];
}

export interface Subject {
  id: number;
  name: string;
}

export interface Class {
  id: number;
  name: string;
  subjects: number[];
}

export interface LeaveApplication {
  id: number;
  teacherId: number;
  teacherName: string;
  leaveType: string;
  startDate: string;
  endDate: string;
  reason: string;
  status: LeaveStatus;
}

export interface AttendanceRecord {
  id: number;
  studentId: number;
  classId: number;
  date: string;
  status: AttendanceStatus;
}

export interface Result {
  id: number;
  studentId: number;
  classId: number;
  resultType: ResultType;
  subject: string;
  marks: number;
  grade: string;
}

export interface Homework {
  id: number;
  classId: number;
  subject: string;
  title: string;
  description: string;
  dueDate: string;
}
