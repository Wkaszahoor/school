<?php

if (!function_exists('discipline_ensure_tables')) {
    function discipline_ensure_tables(mysqli $conn): void
    {
        $conn->query("
            CREATE TABLE IF NOT EXISTS discipline_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                class_id INT NOT NULL,
                category VARCHAR(40) NOT NULL DEFAULT 'warning',
                severity VARCHAR(20) NOT NULL DEFAULT 'medium',
                incident_date DATE NOT NULL,
                title VARCHAR(190) NOT NULL,
                notes TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'open',
                recorded_by_role VARCHAR(20) NOT NULL,
                recorded_by_id INT NOT NULL,
                recorded_by_name VARCHAR(190) NOT NULL,
                report_to_principal TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_discipline_student (student_id),
                INDEX idx_discipline_class (class_id),
                INDEX idx_discipline_status (status),
                INDEX idx_discipline_reporter (recorded_by_role, recorded_by_id)
            )
        ");

        $conn->query("
            CREATE TABLE IF NOT EXISTS discipline_actions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                discipline_id INT NOT NULL,
                action_date DATE NOT NULL,
                action_text TEXT NOT NULL,
                action_by_role VARCHAR(20) NOT NULL,
                action_by_id INT NOT NULL,
                action_by_name VARCHAR(190) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_action_discipline (discipline_id)
            )
        ");

        $conn->query("
            CREATE TABLE IF NOT EXISTS parent_meetings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                discipline_id INT NULL,
                student_id INT NOT NULL,
                class_id INT NOT NULL,
                meeting_date DATE NOT NULL,
                meeting_title VARCHAR(190) NOT NULL,
                attendees TEXT NULL,
                notes TEXT NULL,
                outcome TEXT NULL,
                created_by_role VARCHAR(20) NOT NULL,
                created_by_id INT NOT NULL,
                created_by_name VARCHAR(190) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_parent_meeting_student (student_id),
                INDEX idx_parent_meeting_class (class_id),
                INDEX idx_parent_meeting_discipline (discipline_id)
            )
        ");
    }
}

