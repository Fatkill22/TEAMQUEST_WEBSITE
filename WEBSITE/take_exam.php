<?php
session_start();
include "config.php";

$module_id = $_GET['module_id'];
$questions = $conn->query("SELECT * FROM questions WHERE module_id = $module_id")->fetch_all(MYSQLI_ASSOC);

if (isset($_POST['submit_exam'])) {
    $score = 0;
    foreach ($questions as $q) {
        $user_ans = $_POST['q_' . $q['id']] ?? '';
        if ($user_ans === $q['correct_option']) {
            $score++;
        }
    }
    echo "<div class='alert alert-info mt-5 text-center'><h3>You Scored: $score / ".count($questions)."</h3><a href='EMPLOYEE.php' class='btn btn-primary'>Back Home</a></div>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Module Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
<div class="container bg-white p-4 shadow-sm rounded">
    <h2 class="mb-4">Module Exam</h2>
    <form method="POST">
        <?php foreach($questions as $index => $q): ?>
            <div class="mb-4 p-3 border rounded">
                <h5><?= ($index+1) ?>. <?= htmlspecialchars($q['question_text']) ?></h5>
                <div class="form-check"><input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="A"> <?= $q['option_a'] ?></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="B"> <?= $q['option_b'] ?></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="C"> <?= $q['option_c'] ?></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="D"> <?= $q['option_d'] ?></div>
            </div>
        <?php endforeach; ?>
        <button type="submit" name="submit_exam" class="btn btn-primary btn-lg w-100">Submit Exam</button>
    </form>
</div>
</body>
</html>