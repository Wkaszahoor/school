<?php
include 'db.php';
$r=$conn->query("SELECT group_stream, COUNT(*) c FROM students WHERE class='2nd Year' GROUP BY group_stream ORDER BY c DESC");
while($row=$r->fetch_assoc()){echo json_encode($row).PHP_EOL;}
?>
