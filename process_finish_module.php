<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: EMPLOYEE.php?tab=modules");
    exit();
}

$module_id = (int)$_POST['module_id'];
$next_id   = (int)($_POST['next_id'] ?? 0);
$username  = $_SESSION['username'];

// Only handle modules that have no exam questions
$has_exam = $conn->query("SELECT id FROM questions WHERE module_id = $module_id LIMIT 1")->num_rows > 0;
if ($has_exam) {
    header("Location: view_module.php?id=$module_id");
    exit();
}

// Insert completion record only if not already present
$chk = $conn->prepare("SELECT id FROM exam_results WHERE username = ? AND module_id = ?");
$chk->bind_param("si", $username, $module_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    $ins = $conn->prepare("INSERT INTO exam_results (username, module_id, score, total_questions, attempts, wrong_questions) VALUES (?, ?, 0, 0, 1, '[]')");
    $ins->bind_param("si", $username, $module_id);
    $ins->execute();
}

if ($next_id > 0) {
    header("Location: view_module.php?id=$next_id");
} else {
    header("Location: EMPLOYEE.php?tab=modules");
}
exit();
