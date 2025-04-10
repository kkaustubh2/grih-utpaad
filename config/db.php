<?php
$servername = "localhost";
$username = "root"; 
$password = "7274";     
$dbname = "grih_utpaad";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
