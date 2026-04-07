<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.php");
    exit();
}

$module_id = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 0;

// Handle Adding Questions
if (isset($_POST['add_question'])) {

    $q_text  = $_POST['question_text'];
    $a       = $_POST['option_a'];
    $b       = $_POST['option_b'];
    $c       = $_POST['option_c'];
    $d       = $_POST['option_d'];
    $correct = $_POST['correct_option'];

    $stmt = $conn->prepare("
        INSERT INTO questions 
        (module_id, question_text, option_a, option_b, option_c, option_d, correct_option) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("issssss", $module_id, $q_text, $a, $b, $c, $d, $correct);
    $stmt->execute();
}

// Handle Deleting Questions
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    $conn->query("DELETE FROM questions WHERE id=$id");

    header("Location: manage_exam.php?module_id=$module_id");
    exit();
}

$questions = $conn
    ->query("SELECT * FROM questions WHERE module_id = $module_id")
    ->fetch_all(MYSQLI_ASSOC);

$module_info = $conn
    ->query("SELECT title FROM modules WHERE id = $module_id")
    ->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>

<title>
    Manage Exam - <?= htmlspecialchars($module_info['title']) ?>
</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light p-4">

<div class="container bg-white p-4 shadow-sm rounded">

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>
        Manage Exam: <?= htmlspecialchars($module_info['title']) ?>
    </h2>

    <a href="admin_modules.php" class="btn btn-secondary">
        Back to Admin
    </a>

</div>

<div class="card mb-4 border-primary">

    <div class="card-header bg-primary text-white fw-bold">
        Add New Question
    </div>

    <div class="card-body">

        <form method="POST">

            <textarea name="question_text"
                      class="form-control"
                      placeholder="Question Text"
                      required>
            </textarea>

            <div class="row mb-2">

                <div class="col-md-6">
                    <input type="text"
                           name="option_a"
                           class="form-control"
                           placeholder="Option A"
                           required>
                </div>

                <div class="col-md-6">
                    <input type="text"
                           name="option_b"
                           class="form-control"
                           placeholder="Option B"
                           required>
                </div>

            </div>

            <div class="row mb-2">

                <div class="col-md-6">
                    <input type="text"
                           name="option_c"
                           class="form-control"
                           placeholder="Option C"
                           required>
                </div>

                <div class="col-md-6">
                    <input type="text"
                           name="option_d"
                           class="form-control"
                           placeholder="Option D"
                           required>
                </div>

            </div>

            <select name="correct_option"
                    class="form-select mb-3"
                    required>

                <option value="">Select Correct Option</option>
                <option value="A">Option A</option>
                <option value="B">Option B</option>
                <option value="C">Option C</option>
                <option value="D">Option D</option>

            </select>

            <button type="submit"
                    name="add_question"
                    class="btn btn-primary w-100">

                Add Question

            </button>

        </form>

    </div>

</div>

<h4>Existing Questions</h4>

<table class="table table-bordered align-middle">

    <thead class="table-dark">

        <tr>
            <th>Question</th>
            <th>Correct</th>
            <th class="text-center">Actions</th>
        </tr>

    </thead>

    <tbody>

        <?php foreach ($questions as $q) { ?>

        <tr>

            <td>
                <?= htmlspecialchars($q['question_text']) ?>
            </td>

            <td class="text-center fw-bold text-success">
                <?= $q['correct_option'] ?>
            </td>

            <td class="text-center">

                <a href="edit_question.php?id=<?= $q['id'] ?>&module_id=<?= $module_id ?>"
                   class="btn btn-warning btn-sm">
                    Edit
                </a>

                <a href="?module_id=<?= $module_id ?>&delete=<?= $q['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete?')">
                    Delete
                </a>

            </td>

        </tr>

        <?php } ?>

    </tbody>

</table>

</div>

</body>
</html>