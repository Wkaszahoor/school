# KORT School Management System - Master Delivery Checklist

This checklist maps your full plan to implementation status in this codebase.

Status keys:
- `[x]` Implemented
- `[~]` Partial / needs upgrade
- `[ ]` Not implemented

## 1. User Roles and Dashboards
- `[x]` Admin dashboard (core screens exist)
- `[ ]` Principal separate login/dashboard
- `[~]` Teacher dashboard (attendance/marks/lesson/homework/behaviour exist, needs strict RBAC)
- `[x]` Receptionist dashboard
- `[x]` Principal Helper dashboard
- `[x]` Inventory Manager dashboard
- `[x]` Central role-permission matrix (RBAC engine)

## 2. Student Information System (Enhanced)
- `[x]` Student basic profile
- `[x]` Class/year and stream/group support
- `[x]` Added extended profile fields (guardian, join date KORT, orphan status, blood group, etc.)
- `[x]` Real image upload/storage pipeline (currently path/url field)
- `[x]` Document upload manager with validation/versioning
- `[x]` Sensitive trust notes visibility only for Admin/Principal

## 3. Discipline and Remarks
- `[~]` Behaviour reporting exists in teacher panel
- `[x]` Dedicated discipline module with warnings/achievements/action history
- `[x]` Parent meeting log
- `[x]` Role-based access boundaries for discipline views/actions

## 4. Examination and Results
- `[x]` Exam types + mark entry + grade/percentage
- `[x]` Group-aware filtering in marks entry for 9-12 streams
- `[~]` Result viewing/export (basic)
- `[x]` Trend analytics and comparison dashboards
- `[x]` Result cards (PDF), gazette (Excel/PDF), top positions
- `[x]` Result locking workflow + approval

## 5. Attendance (Mobile Friendly)
- `[x]` Teacher attendance marking + absent reporting
- `[~]` Admin attendance view/reporting
- `[x]` Mobile-first UI optimization pass
- `[x]` Low attendance alerts + automated notifications
- `[x]` Studentwise/classwise export to PDF with templates

## 6. Lesson Plan Module
- `[x]` Teacher lesson plan submission
- `[x]` Principal comments / approve / return for edits
- `[x]` Submission compliance tracking per teacher

## 7. Notice Board and Broadcast
- `[x]` Notice posting module (principal/admin can post notices)
- `[x]` Targeted broadcast (all/specific roles/teachers/classes)
- `[x]` Read/unread tracking (NoticeRead model + UI)
- `[x]` Dashboard popup notifications (bell icon with unread count and dropdown)

## 8. Document Printing
- `[x]` Student ID card generation
- `[x]` Result card PDF
- `[x]` Bonafide / leaving / character certificate templates
- `[x]` Optional QR code embedding

## 9. Audit Logs
- `[ ]` Audit log table and logger service
- `[ ]` Track edits for marks, attendance, students, assignments, result lock
- `[ ]` Old value -> new value capture
- `[ ]` Admin/Principal audit viewer

## 10. Security and Privacy
- `[ ]` RBAC enforcement middleware/helpers
- `[ ]` Strong password policy
- `[ ]` Sensitive field encryption
- `[ ]` 2FA (optional toggle)
- `[~]` Session logout exists; timeout policy not complete

## 11. Backup and Export
- `[ ]` Daily automatic DB backup
- `[ ]` Manual backup action for Admin
- `[~]` Some CSV exports implemented
- `[ ]` Unified export service (Excel/PDF across all modules)

## 12. Inventory Management
- `[x]` Item categories
- `[x]` Stock in/out ledger
- `[x]` Issue records to teacher/class
- `[x]` Low stock alerts
- `[x]` Monthly inventory reports

## Delivery Phases

## Phase A - Foundation (must complete first)
- `[ ]` Create `users`, `roles`, `permissions`, `user_roles`, `role_permissions` schema
- `[ ]` Move logins to unified auth by role
- `[ ]` Protect all routes by role
- `[ ]` Introduce audit log service and wire critical mutations

## Phase B - Academic Core
- `[ ]` Principal dashboard and approval flows
- `[ ]` Notice/broadcast module
- `[ ]` Result publish/lock pipeline
- `[ ]` Attendance alerting

## Phase C - Compliance and Operations
- `[ ]` Orphan trust sensitive records access control
- `[ ]` Document generation (PDF templates)
- `[ ]` Backup scheduler + restore docs
- `[ ]` Inventory module

## Immediate Next Build Order
1. Implement RBAC schema + auth guard helpers.
2. Add Principal role + dashboard + approval workflows.
3. Add audit logs for student/marks/attendance/assignment changes.
4. Add notice board + broadcast.
5. Add document print module and inventory module.
