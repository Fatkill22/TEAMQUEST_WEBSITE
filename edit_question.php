<?php
session_start();
include "config.php";

// Admin check
if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.php");
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$module_id = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 0;

// Fetch existing question data
$stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$question = $stmt->get_result()->fetch_assoc();

if (!$question) {
    die("Question not found.");
}

// Handle Update Logic
if (isset($_POST['update_question'])) {

    $q_text  = $_POST['question_text'];
    $a       = $_POST['option_a'];
    $b       = $_POST['option_b'];
    $c       = $_POST['option_c'];
    $d       = $_POST['option_d'];
    $correct = $_POST['correct_option'];

    $update = $conn->prepare("
        UPDATE questions 
        SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=? 
        WHERE id=?
    ");

    $update->bind_param("ssssssi", $q_text, $a, $b, $c, $d, $correct, $id);

    if ($update->execute()) {
        header("Location: manage_exam.php?module_id=" . $module_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Question - TeamQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light p-5">

<div class="container bg-white p-4 shadow rounded" style="max-width: 800px;">

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-warning">Edit Question</h2>
        <a href="manage_exam.php?module_id=<?= $module_id ?>" class="btn btn-outline-secondary">
            Back
        </a>
    </div>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label fw-bold">Question Text</label>
            <textarea name="question_text"
                      class="form-control"
                      rows="4"
                      required><?= htmlspecialchars($question['question_text']) ?></textarea>
        </div>

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">Option A</label>
                <input type="text"
                       name="option_a"
                       class="form-control"
                       value="<?= htmlspecialchars($question['option_a']) ?>"
                       required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Option B</label>
                <input type="text"
                       name="option_b"
                       class="form-control"
                       value="<?= htmlspecialchars($question['option_b']) ?>"
                       required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Option C</label>
                <input type="text"
                       name="option_c"
                       class="form-control"
                       value="<?= htmlspecialchars($question['option_c']) ?>"
                       required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Option D</label>
                <input type="text"
                       name="option_d"
                       class="form-control"
                       value="<?= htmlspecialchars($question['option_d']) ?>"
                       required>
            </div>

        </div>

        <div class="mb-4">
            <label class="form-label fw-bold text-primary border-bottom border-primary">
                Correct Answer
            </label>

            <div class="p-3 bg-light rounded">
                <select name="correct_option"
                        class="form-select fw-bold"
                        required>

                    <option value="A" <?= $question['correct_option'] == 'A' ? 'selected' : '' ?>>
                        Option A
                    </option>

                    <option value="B" <?= $question['correct_option'] == 'B' ? 'selected' : '' ?>>
                        Option B
                    </option>

                    <option value="C" <?= $question['correct_option'] == 'C' ? 'selected' : '' ?>>
                        Option C
                    </option>

                    <option value="D" <?= $question['correct_option'] == 'D' ? 'selected' : '' ?>>
                        Option D
                    </option>

                </select>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit"
                    name="update_question"
                    class="btn btn-warning btn-lg fw-bold">
                Save Changes
            </button>

            <a href="manage_exam.php?module_id=<?= $module_id ?>"
               class="btn btn-link text-muted">
               Discard Changes
            </a>
        </div>

    </form>

</div>

</body>
</html>