<?php
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $empnumber = trim($_POST['empnumber']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);

    // Username = Firstname + Lastname
    $username = strtolower($firstname . $lastname);

    // Password = Employee Number
    $password = $empnumber;

    // Role logic
    if ($department == "HR/ADMIN") {
        $roles = "admin";
    } else {
        $roles = "employee";
    }

    // INSERT QUERY (firstname and lastname added)
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, empnumber, username, password, department, position, roles) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssss", $firstname, $lastname, $empnumber, $username, $password, $department, $position, $roles);

    if ($stmt->execute()) {

        echo "<script>
                alert('Registration successful');
                window.location.href='INDEX.html';
              </script>";

    } else {

        echo "<script>
                alert('Error registering user');
                window.location.href='REGISTER.html';
              </script>";
    }

    $stmt->close();
    $conn->close();
}
?>