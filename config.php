<?php
$conn = mysqli_connect("localhost", "root", "", "online_training");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>