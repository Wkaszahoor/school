<?php
include 'db.php';
$tables=['class_stream_subject_groups','subject_group_subjects','subject_groups','class_subjects','classes'];
foreach($tables as $t){
  $q=$conn->query("SHOW TABLES LIKE '$t'");
  echo $t.':'.($q&&$q->num_rows?"yes":"no").PHP_EOL;
}
if($conn->query("SHOW TABLES LIKE 'class_stream_subject_groups'")->num_rows){
  $r=$conn->query("SELECT class_id, stream_key, group_id FROM class_stream_subject_groups ORDER BY class_id, stream_key LIMIT 50");
  echo "--class_stream_subject_groups--".PHP_EOL;
  while($row=$r->fetch_assoc()){echo json_encode($row).PHP_EOL;}
}
if($conn->query("SHOW TABLES LIKE 'classes'")->num_rows){
  $r=$conn->query("SELECT id,class,academic_year FROM classes WHERE id=20 LIMIT 1");
  echo "--class 20--".PHP_EOL;
  while($row=$r->fetch_assoc()){echo json_encode($row).PHP_EOL;}
}
if($conn->query("SHOW TABLES LIKE 'class_subjects'")->num_rows){
  $r=$conn->query("SELECT cs.class_id, s.subject_name FROM class_subjects cs JOIN subjects s ON s.id=cs.subject_id WHERE cs.class_id=20 ORDER BY s.subject_name");
  echo "--class20 subjects--".PHP_EOL;
  while($row=$r->fetch_assoc()){echo json_encode($row).PHP_EOL;}
}
?>
