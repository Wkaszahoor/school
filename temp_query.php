<?php
require 'db.php';
$result = $conn->query("SELECT id, class, section FROM classes WHERE class LIKE '9%' ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | Class: {$row['class']} | Section: " . ($row['section'] ?? 'NULL') . "\n";
}
