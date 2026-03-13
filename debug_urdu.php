<?php
$conn = new mysqli('127.0.0.1', 'root', 'mysql', 'db_school_kort');

echo "=== Teachers Teaching URDU ===\n\n";
$result = $conn->query("
    SELECT DISTINCT u.id, u.name, s.subject_name, c.class, c.section, ta.assignment_type
    FROM users u
    JOIN teacher_assignments ta ON u.id = ta.teacher_id
    JOIN subjects s ON ta.subject_id = s.id
    JOIN classes c ON ta.class_id = c.id
    WHERE s.subject_name = 'Urdu'
    ORDER BY u.name, c.class
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['name'] . " (ID: " . $row['id'] . ") → " . $row['subject_name'] . " in " . $row['class'] . "-" . $row['section'] . " (" . $row['assignment_type'] . ")\n";
    }
} else {
    echo "No teachers found teaching Urdu\n";
}

echo "\n=== Umair Nazar Full Assignment Details ===\n\n";
$result2 = $conn->query("
    SELECT u.id, u.name, u.email, s.subject_name, c.class, c.section, ta.assignment_type
    FROM users u
    JOIN teacher_assignments ta ON u.id = ta.teacher_id
    JOIN subjects s ON ta.subject_id = s.id
    JOIN classes c ON ta.class_id = c.id
    WHERE u.name LIKE '%Umair%'
    ORDER BY c.class, ta.assignment_type
");

if ($result2->num_rows > 0) {
    echo "Found Umair Nazar:\n";
    while ($row = $result2->fetch_assoc()) {
        echo "  ID: " . $row['id'] . " | Email: " . $row['email'] . " | " . $row['subject_name'] . " in " . $row['class'] . "-" . $row['section'] . " (" . $row['assignment_type'] . ")\n";
    }
} else {
    echo "Umair Nazar not found in teacher_assignments\n";
}

echo "\n=== Check all teachers with Urdu assignments ===\n\n";
$result3 = $conn->query("
    SELECT DISTINCT ta.teacher_id, u.name, COUNT(*) as urdu_count
    FROM teacher_assignments ta
    JOIN users u ON ta.teacher_id = u.id
    JOIN subjects s ON ta.subject_id = s.id
    WHERE s.subject_name = 'Urdu'
    GROUP BY ta.teacher_id, u.name
");

if ($result3->num_rows > 0) {
    while ($row = $result3->fetch_assoc()) {
        echo $row['name'] . " (ID: " . $row['teacher_id'] . ") has " . $row['urdu_count'] . " Urdu assignments\n";
    }
}

$conn->close();
?>
