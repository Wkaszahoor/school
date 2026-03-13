<?php
// Central DB include for new modules.
require_once __DIR__ . '/db.php';

// Safety net: some modules include config.php after another file that may not keep
// $conn in scope. Ensure we always return a valid mysqli connection.
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = mysqli_connect('localhost', 'root', 'mysql', 'db_school_kort');
    if (!$conn) {
        die('Connection failed: ' . mysqli_connect_error());
    }
}
