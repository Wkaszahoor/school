# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

KORT School Management System — a hybrid PHP + TypeScript/Vite application for UK-based school KORT (https://kort.org.uk). Manages students, academics, attendance, discipline, medical records, and inventory across 7 distinct user roles.

## Commands

### Frontend (TypeScript + React + Vite)
```bash
npm install          # Install Node dependencies
npm run dev          # Start dev server on http://localhost:3000
```

Set `GEMINI_API_KEY` in `.env.local` before running.

### Backend
PHP runs directly via a web server (Apache/Nginx + PHP-FPM). No build step required.

Database: MySQL/MariaDB — `db_school_kort` on `localhost`, user `root`, password `mysql` (see `db.php`).

## Architecture

### Hybrid structure
- **Backend:** PHP 7.4+ with MySQLi, session-based auth, no framework
- **Frontend layer:** Vite + React + TypeScript (port 3000), used for AI/Gemini-powered features
- **UI:** Bootstrap 5.3, Chart.js, DataTables, Font Awesome 6.5 (served from `/vendor`)
- **Path alias:** `@/` maps to the repo root in TypeScript

### Authentication & RBAC — `auth.php`
This is the central engine. All pages must include it and call `auth_require_permission()`. Key functions:

| Function | Purpose |
|----------|---------|
| `auth_login_user($role, $id, $email, $name)` | Start authenticated session |
| `auth_require_role($role)` | Redirect if wrong role |
| `auth_require_permission($resource, $action, $redirect)` | RBAC gate for any page |
| `auth_can($resource, $action)` | Returns bool; `admin` always returns true |
| `auth_audit_log(...)` | Write to audit_logs table |
| `auth_dashboard_for_role($role)` | Returns dashboard URL for role |

The permission matrix lives in `auth_permissions()` inside `auth.php`. **`ROLE_PERMISSION_MATRIX.md` is the human-readable reference** — the source of truth is the code.

To add a new permission: update `auth_permissions()` in `auth.php` and call `auth_require_permission()` on the new page.

### Role → Dashboard mapping

| Role | Dashboard path |
|------|---------------|
| `admin` | `/admin/index1.php` |
| `principal` | `/principal/dashboard.php` |
| `teacher` | `/students/index.php` |
| `receptionist` | `/receptionist/dashboard.php` |
| `principal_helper` | `/helper/dashboard.php` |
| `inventory_manager` | `/inventory/dashboard.php` |
| `doctor` | `/doctor/records.php` |

### Database connection
`db.php` opens the MySQLi connection. `config.php` is a safety net that re-includes `db.php` if `$conn` is not in scope. All queries use prepared statements.

### Password verification
`auth_verify_password()` supports bcrypt (preferred), MD5 hex (legacy), and plaintext (fallback). New accounts should always use bcrypt.

### Streams/groups
Classes 9–12 support multiple streams/groups for subject grouping and marks filtering. This affects student assignment (`assign_group`) and marks entry.

### File uploads
Stored under `/uploads` and `/upload` directories.

## Key reference files

- `auth.php` — RBAC engine, session management, audit logging
- `db.php` — Database connection
- `IMPLEMENTATION_CHECKLIST.md` — Feature status (implemented / partial / not started)
- `ROLE_PERMISSION_MATRIX.md` — Human-readable permissions reference
- `vite.config.ts` — Frontend build config
