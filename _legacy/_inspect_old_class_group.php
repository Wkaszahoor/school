<?php
include 'db.php';
$r=$conn->query("SELECT class_id, group_id FROM class_subject_groups ORDER BY class_id LIMIT 50");
echo "--class_subject_groups--\n";
if($r){while($row=$r->fetch_assoc()) echo json_encode($row)."\n";} else {echo "query-failed\n";}
?>
