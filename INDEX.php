<?php
ob_start();
session_start();
include "config.php";

// Direct browser visits (no form submission) → send to login page
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: INDEX.html");
    ob_end_flush();
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Guard: empty fields
if ($username === '' || $password === '') {
    header("Location: INDEX.html?error=invalid");
    ob_end_flush();
    exit();
}

// Guard: DB connection
if (!$conn) {
    header("Location: INDEX.html?error=1");
    ob_end_flush();
    exit();
}

$stmt = $conn->prepare(
    "SELECT username, password, empnumber, firstname, lastname, department, roles
     FROM users WHERE username = ?"
);

// Guard: prepare() failure
if (!$stmt) {
    header("Location: INDEX.html?error=1");
    ob_end_flush();
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $user = $res->fetch_assoc();

    $stored   = (string)($user['password']  ?? '');
    $fallback = (string)($user['empnumber'] ?? '');

    // Match: stored password, OR emp-number when password column is empty
    $match = ($stored !== '' && $password === $stored)
          || ($stored === '' && $password === $fallback);

    if ($match) {
        $_SESSION['username']   = $user['username'];
        $_SESSION['firstname']  = $user['firstname'];
        $_SESSION['lastname']   = $user['lastname'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['roles']      = strtolower($user['roles']);

        $dest = ($_SESSION['roles'] === 'admin') ? 'admin_modules.php' : 'EMPLOYEE.php';
        header("Location: $dest");
        ob_end_flush();
        exit();
    }
}

// Credentials did not match
header("Location: INDEX.html?error=invalid");
ob_end_flush();
exit();
?>
