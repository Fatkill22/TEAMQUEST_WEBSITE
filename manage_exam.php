<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.php"); exit();
}

$module_id = $_GET['module_id'];

// Handle Adding Question
if (isset($_POST['add_question'])) {
    $q = $_POST['question'];
    $a = $_POST['option_a']; $b = $_POST['option_b'];
    $c = $_POST['option_c']; $d = $_POST['option_d'];
    $ans = $_POST['correct'];

    $stmt = $conn->prepare("INSERT INTO questions (module_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $module_id, $q, $a, $b, $c, $d, $ans);
    $stmt->execute();
}

$questions = $conn->query("SELECT * FROM questions WHERE module_id = $module_id")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Exam Questions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
    <a href="admin_modules.php" class="btn btn-secondary mb-3">Back to Modules</a>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">Add New Question</div>
        <div class="card-body">
            <form method="POST">
                <textarea name="question" class="form-control mb-2" placeholder="Question Text" required></textarea>
                <div class="row">
                    <div class="col-md-6 mb-2"><input type="text" name="option_a" class="form-control" placeholder="Option A" required></div>
                    <div class="col-md-6 mb-2"><input type="text" name="option_b" class="form-control" placeholder="Option B" required></div>
                    <div class="col-md-6 mb-2"><input type="text" name="option_c" class="form-control" placeholder="Option C" required></div>
                    <div class="col-md-6 mb-2"><input type="text" name="option_d" class="form-control" placeholder="Option D" required></div>
                </div>
                <select name="correct" class="form-select mb-3" required>
                    <option value="">Select Correct Answer</option>
                    <option value="A">Option A</option>
                    <option value="B">Option B</option>
                    <option value="C">Option C</option>
                    <option value="D">Option D</option>
                </select>
                <button type="submit" name="add_question" class="btn btn-success w-100">Add to Exam</button>
            </form>
        </div>
    </div>

    <h4>Current Questions</h4>
    <?php foreach($questions as $q): ?>
        <div class="card mb-2">
            <div class="card-body">
                <strong>Q: <?= htmlspecialchars($q['question_text']) ?></strong><br>
                <small>A: <?= $q['option_a'] ?> | B: <?= $q['option_b'] ?> | Correct: <b><?= $q['correct_option'] ?></b></small>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>