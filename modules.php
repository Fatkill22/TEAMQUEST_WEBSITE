<?php
include "config.php";

$department = "all"; // default

if (isset($_GET['department'])) {
    $department = $_GET['department'];
}

if ($department == "all") {
    $sql = "SELECT * FROM modules";
} else {
    $sql = "SELECT * FROM modules WHERE department=?";
}

$stmt = $conn->prepare($sql);

if ($department != "all") {
    $stmt->bind_param("s", $department);
}

$stmt->execute();
$result = $stmt->get_result();

$modules = [];
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}

$stmt->close();
$conn->close();

return $modules;
?>