# Teacher Assignment Management Guide

## Overview
The Teacher Assignment system allows Principals to assign teachers to classes and subjects on a **class-wise and subject-wise basis** for specific academic years.

## How to Access

**Path:** Principal Dashboard → **Academic** → **Teacher Assignments**

Or navigate directly to: `/principal/teacher-assignments`

## Features

### 1. View All Assignments
The main page displays a table of all active teacher assignments with:
- **Teacher Name** - The assigned teacher
- **Class** - The class number and section (e.g., 9A, 10B)
- **Subject** - The subject being taught
- **Academic Year** - The year for which the assignment is valid (e.g., 2025-26)
- **Action** - Delete button to remove assignments

### 2. Create New Assignment

**Step 1:** Click the **"New Assignment"** button

**Step 2:** Fill in the assignment form with:
- **Teacher** - Select a teacher from the dropdown
- **Class** - Select the class (searchable dropdown showing all active classes with sections)
- **Subject** - Select the subject to be taught
- **Academic Year** - Select the academic year (default: 2025-26)

**Step 3:** Click **"Assign Teacher"**

The system will:
- ✅ Validate that the teacher is active
- ✅ Validate that the class exists
- ✅ Validate that the subject exists
- ✅ Prevent duplicate assignments (same teacher + class + subject + year)
- ✅ Log the assignment in audit logs
- ✅ Show success/error messages

### 3. Remove Assignment

1. Find the assignment in the table
2. Click the **red trash icon** in the Action column
3. Confirm the deletion
4. The assignment will be removed and logged in audit logs

## Usage Scenarios

### Scenario 1: New Academic Year
When starting a new academic year:
1. Go to Teacher Assignments
2. Create new assignments for all teachers
3. Select the new academic year (e.g., 2026-27)
4. Assign each teacher to their classes and subjects

### Scenario 2: Teacher Reassignment
If a teacher is reassigned:
1. Delete the old assignment
2. Create a new assignment with the updated class/subject
3. Or modify for the next academic year

### Scenario 3: Multi-Class Teachers
If a teacher teaches multiple classes or subjects:
1. Create separate assignment entries
2. Example: Mr. Ahmed teaches Math to 9A and 9B
   - Assignment 1: Mr. Ahmed + 9A + Math + 2025-26
   - Assignment 2: Mr. Ahmed + 9B + Math + 2025-26

## Impact on Other Modules

Once a teacher is assigned to a class and subject:

### For Teachers:
- ✅ Can only mark attendance for their assigned classes
- ✅ Can only enter results for their assigned subjects
- ✅ See only their assigned lesson plans
- ✅ Access filtered to their class/subject combinations

### For Principal:
- ✅ Can see all teacher assignments
- ✅ Can view results filtered by teacher
- ✅ Can monitor lesson plans by assigned teacher

### For Admin:
- ✅ Can view teacher assignment history in audit logs
- ✅ Can see teacher employment records with their class/subject load

## Audit Logging

All assignment changes are logged:
- **Create Assignment** - Logged when a new assignment is created
- **Delete Assignment** - Logged when an assignment is removed
- Accessible in: **Admin Dashboard → Audit Logs**

## Validation Rules

The system enforces:
1. ✅ Teacher must exist and be active
2. ✅ Class must exist and be active
3. ✅ Subject must exist and be active
4. ✅ No duplicate assignments allowed (same teacher + class + subject + year)
5. ✅ Academic year must be valid (2024-25, 2025-26, 2026-27)

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Can't find a teacher in dropdown | Check if teacher is active (deactivated teachers don't appear) |
| Can't find a class in dropdown | Verify the class is active in Classes management |
| Can't find a subject in dropdown | Check if subject is active in Subject management |
| "Assignment already exists" error | The teacher-class-subject combination already exists for that year; delete the old one first |
| Changes not appearing | Refresh the page (Ctrl+R) |

## Best Practices

1. **Plan Before Assigning** - Prepare a list of all assignments before entering them
2. **Verify Data** - Ensure classes and subjects are active before assigning
3. **Annual Review** - Review and update assignments at the start of each academic year
4. **Audit Trail** - Check audit logs if unsure about assignment history
5. **Balance Load** - Try to balance the number of classes per teacher

## Technical Notes

- Assignments are stored with **teacher_id** (not name) to maintain referential integrity
- Each assignment is unique per combination of: Teacher + Class + Subject + Academic Year
- Deleted assignments are soft-logged and can be traced in audit logs
- The system supports multiple academic years simultaneously
