<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

$module_id = (int) $_GET['module_id'];
$username  = $_SESSION['username'];

$full_name =
    (isset($_SESSION['firstname']) ? $_SESSION['firstname'] : '') . " " .
    (isset($_SESSION['lastname'])  ? $_SESSION['lastname']  : '');

// Check attempts
$check = $conn->query(
    "SELECT attempts 
     FROM exam_results 
     WHERE username = '$username' 
     AND module_id = $module_id"
);

$attempt_data     = $check->fetch_assoc();
$current_attempts = $attempt_data ? $attempt_data['attempts'] : 0;

if ($current_attempts >= 3) {
    die("
    <div class='container mt-5 text-center'>
        <h2>Limit Reached</h2>
        <p>You have used your 3 attempts.</p>
        <a href='EMPLOYEE.php'>Back</a>
    </div>
    ");
}

$questions = $conn->query(
    "SELECT * FROM questions WHERE module_id = $module_id"
)->fetch_all(MYSQLI_ASSOC);

if (isset($_POST['submit_exam'])) {

    $score = 0;
    $total = count($questions);

    foreach ($questions as $q) {
        if (($_POST['q_' . $q['id']] ?? '') === $q['correct_option']) {
            $score++;
        }
    }

    if ($attempt_data) {

        $conn->query(
            "UPDATE exam_results 
             SET score = $score, attempts = attempts + 1 
             WHERE username = '$username' 
             AND module_id = $module_id"
        );

    } else {

        $conn->query(
            "INSERT INTO exam_results 
             (username, module_id, score, total_questions, attempts) 
             VALUES ('$username', $module_id, $score, $total, 1)"
        );
    }
?>

<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light p-5 text-center">

<div class="container bg-white p-5 shadow rounded" style="max-width: 600px;">

    <h1>Result</h1>
    <hr>

    <h4>
        Examinee:
        <?php echo htmlspecialchars($full_name ?: $username); ?>
    </h4>

    <div class="alert <?php echo ($score/$total >= 0.7) ? 'alert-success' : 'alert-danger'; ?> my-4">

        <h2>
            Score: <?php echo $score; ?> / <?php echo $total; ?>
        </h2>

        <p>
            Attempt: <?php echo $current_attempts + 1; ?> of 3
        </p>

    </div>

    <a href="EMPLOYEE.php" class="btn btn-primary">
        Finish
    </a>

</div>

</body>
</html>

<?php
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light p-4">

<div class="container bg-white p-4 shadow-sm rounded" style="max-width: 800px;">

    <h3>
        Exam - Attempt <?php echo $current_attempts + 1; ?> / 3
    </h3>

    <form method="POST">

        <?php foreach ($questions as $idx => $q): ?>

        <div class="mb-4">

            <p class="fw-bold">
                <?= ($idx + 1) ?>.
                <?= htmlspecialchars($q['question_text']) ?>
            </p>

            <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>

            <div class="form-check">

                <input class="form-check-input"
                       type="radio"
                       name="q_<?= $q['id'] ?>"
                       value="<?= $opt ?>"
                       required>

                <?= $opt ?>)
                <?= htmlspecialchars($q['option_' . strtolower($opt)]) ?>

            </div>

            <?php endforeach; ?>

        </div>

        <?php endforeach; ?>

        <button type="submit"
                name="submit_exam"
                class="btn btn-success w-100">
            Submit
        </button>

    </form>

</div>

</body>
</html>