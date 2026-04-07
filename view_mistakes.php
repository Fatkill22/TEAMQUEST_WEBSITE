<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) { 
    header("Location: INDEX.php"); 
    exit(); 
}

$module_id = (int)$_GET['module_id'];
$username = $_SESSION['username'];

// Fetch the saved wrong question IDs
$res = $conn->query("SELECT wrong_questions FROM exam_results WHERE username = '$username' AND module_id = $module_id");
$data = $res->fetch_assoc();

if (!$data || empty($data['wrong_questions'])) {
    die("<div class='container mt-5 text-center'>
            <h3>No mistakes recorded for this module!</h3>
            <a href='EMPLOYEE.php' class='btn btn-primary mt-3'>Back</a>
        </div>");
}

// 🔒 SANITIZE IDS (IMPORTANT FIX)
$ids_array = array_filter(explode(',', $data['wrong_questions']), function($id) {
    return is_numeric($id);
});

if (empty($ids_array)) {
    die("<div class='container mt-5 text-center'>
            <h3>No valid mistake data found.</h3>
            <a href='EMPLOYEE.php' class='btn btn-primary mt-3'>Back</a>
        </div>");
}

// Convert back to safe string
$ids = implode(',', $ids_array);

// Fetch only those questions
$questions = $conn
    ->query("SELECT * FROM questions WHERE id IN ($ids)")
    ->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Previous Mistakes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">

<div class="container bg-white p-5 shadow rounded" style="max-width: 800px;">

    <h2 class="text-danger mb-4">Your Previous Mistakes</h2>
    <p class="text-muted">
        Review these questions before your next attempt
    </p>
    <hr>

    <?php foreach ($questions as $q): ?>
        <div class="mb-4 p-3 border-start border-danger border-4 bg-light shadow-sm">
            <h5 class="fw-bold"><?= htmlspecialchars($q['question_text']) ?></h5>
            <ul class="list-unstyled mt-2">
                <li>A) <?= htmlspecialchars($q['option_a']) ?></li>
                <li>B) <?= htmlspecialchars($q['option_b']) ?></li>
                <li>C) <?= htmlspecialchars($q['option_c']) ?></li>
                <li>D) <?= htmlspecialchars($q['option_d']) ?></li>
            </ul>
        </div>
    <?php endforeach; ?>

    <a href="EMPLOYEE.php" class="btn btn-primary mt-3">
        Return to Dashboard
    </a>

</div>

</body>
</html>