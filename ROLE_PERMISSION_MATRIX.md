# Role Permission Matrix

Source of truth in code: `auth.php` (`auth_permissions()`).

## Roles
- `admin`
- `principal`
- `teacher`
- `receptionist`
- `principal_helper`
- `inventory_manager`
- `doctor`

## Resources and Actions
- `class_year`: `view`, `create`, `edit`, `delete`
- `students`: `view`, `create`, `edit`, `delete`, `import`, `export`, `assign_group`, `populate`
- `teachers`: `view`, `create`, `edit`, `delete`, `assign`
- `subjects`: `view`, `create`, `edit`, `delete`, `seed`
- `attendance_reports`: `view`, `create`, `edit`, `approve`, `export`
- `results_reports`: `view`, `create`, `edit`, `approve`, `export`
- `staff_users`: `view`, `create`, `edit`, `delete`
- `admin_dashboard`: `view`
- `medical_records`: `view`, `create`, `edit`, `approve`
- `teacher_profile`: `view`, `edit`, `change_password`
- `teacher_workspace`: `view`, `create`, `edit`, `export`
- `doctor_records`: `view`, `approve`, `examine`
- `inventory`: `view`, `create`, `edit`, `delete`, `export`

## Notes
- `admin` currently has full override access in `auth_can()` for operational safety.
- To hard-enforce strict matrix for admin too, remove the admin override block in `auth_can()`.
- Add new permissions by updating `auth_permissions()` and calling `auth_require_permission($resource, $action, $redirect)` in target pages.

