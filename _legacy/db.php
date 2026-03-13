<?php
$servername = "localhost"; 
$username = "root";
$password = "mysql"; 
$dbname = "db_school_kort"; 

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
