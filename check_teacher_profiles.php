<?php
$conn = new mysqli('127.0.0.1', 'root', 'mysql', 'db_school_kort');

echo "Sample Teachers and their teacher_profile_id:\n\n";
$result = $conn->query("SELECT id, name, teacher_profile_id, role FROM users WHERE role = 'teacher' LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | teacher_profile_id: " . ($row['teacher_profile_id'] ?? 'NULL') . " | Role: " . $row['role'] . "\n";
}

echo "\n\nTeachers with NULL teacher_profile_id:\n";
$nullResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND teacher_profile_id IS NULL");
$nullRow = $nullResult->fetch_assoc();
echo "Count: " . $nullRow['count'] . "\n";

echo "\n\nTeacher Profiles that don't have a user link:\n";
$orphanResult = $conn->query("
    SELECT tp.id, tp.user_id FROM teacher_profiles tp
    LEFT JOIN users u ON tp.user_id = u.id
    WHERE u.id IS NULL
");
echo "Orphaned profiles: " . $orphanResult->num_rows . "\n";

$conn->close();
?>
