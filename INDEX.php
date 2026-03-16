<?php
session_start();
include "config.php";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT username, password, department, roles FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        
        if ($password === $user['password']) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['roles'] = strtolower($user['roles']); // Normalize to lowercase

            if ($_SESSION['roles'] == 'admin') {
                header("Location: admin_modules.php");
            } else {
                header("Location: EMPLOYEE.php");
            }
            exit();
        }
    }
    echo "<script>alert('Invalid Credentials'); window.location.href='INDEX.html';</script>";
}
?>