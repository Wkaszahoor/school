<?php
$conn = new mysqli('127.0.0.1', 'root', 'mysql', 'db_school_kort');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Umair Nazar Current Database Assignments ===\n\n";
$result = $conn->query("
    SELECT u.name, s.subject_name, c.class, c.section, ta.assignment_type, ta.academic_year
    FROM users u
    JOIN teacher_assignments ta ON u.id = ta.teacher_id
    JOIN subjects s ON ta.subject_id = s.id
    JOIN classes c ON ta.class_id = c.id
    WHERE u.name = 'Umair Nazar'
    ORDER BY c.class, ta.assignment_type
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['class'] . '-' . $row['section'] . ' | ' . $row['subject_name'] . ' | ' . $row['assignment_type'] . ' | ' . $row['academic_year'] . "\n";
    }
} else {
    echo "No assignments found\n";
}

echo "\n=== Expected from Book2.csv ===\n";
echo "Name: Umair Nazar\n";
echo "Subject: English\n";
echo "Classes: 2ND YEAR, 1ST YEAR, 10TH, 9-A, 9-B\n";
echo "Class Teacher For: 1ST YEAR\n";

$conn->close();
