<?php
session_start();
include "config.php";

if (isset($_POST['login'])) {

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($username && $password) {

        $stmt = $conn->prepare("SELECT username, password, department, roles FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($db_username, $db_password, $db_department, $db_roles);
            $stmt->fetch();

            $db_password = trim($db_password);
            $db_roles = trim($db_roles);

            if ($password === $db_password) {

                $_SESSION['username'] = $db_username;
                $_SESSION['department'] = $db_department;
                $_SESSION['roles'] = $db_roles;

                if (strtolower($db_roles) == 'admin') {
                    header("Location: admin_modules.php");
                } else {
                    header("Location: EMPLOYEE.php");
                }
                exit();

            } else {
                echo "<p style='color:red;text-align:center;'>Incorrect password.</p>";
            }

        } else {
            echo "<p style='color:red;text-align:center;'>Username not found.</p>";
        }

    } else {
        echo "<p style='color:red;text-align:center;'>Please enter both username and password.</p>";
    }

} else {
    header("Location: INDEX.html");
    exit();
}
?>