<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

$module_id = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 0;
$username  = $_SESSION['username'];

// Get User Name for display
$first = isset($_SESSION['firstname']) ? $_SESSION['firstname'] : '';
$last  = isset($_SESSION['lastname']) ? $_SESSION['lastname'] : '';
$full_name = trim($first . " " . $last) ?: $username;

// Check attempts
$check = $conn->query("SELECT attempts FROM exam_results WHERE username = '$username' AND module_id = $module_id");
$attempt_data = $check->fetch_assoc();
$current_attempts = $attempt_data ? $attempt_data['attempts'] : 0;

if ($current_attempts >= 3) {
    die("
        <div class='container mt-5 text-center'>
            <h2>Limit Reached</h2>
            <p>Maximum 3 attempts used.</p>
            <a href='EMPLOYEE.php' class='btn btn-primary'>Back</a>
        </div>
    ");
}

$questions = $conn
    ->query("SELECT * FROM questions WHERE module_id = $module_id")
    ->fetch_all(MYSQLI_ASSOC);


// --- START NEW FEATURE: SUBMISSION LOGIC ---
if (isset($_POST['submit_exam'])) {

    $score = 0;
    $total = count($questions);
    $mistakes = [];
    $wrong_ids = [];

    foreach ($questions as $q) {

        $user_ans = $_POST['q_' . $q['id']] ?? null;

        if ($user_ans && $user_ans === $q['correct_option']) {
            $score++;
        } else {

            $option_key = $user_ans ? 'option_' . strtolower($user_ans) : null;

            $mistakes[] = [
                'question'         => $q['question_text'],
                'user_choice'      => $user_ans ?? 'None',
                'user_choice_text' => ($option_key && isset($q[$option_key])) 
                                        ? $q[$option_key] 
                                        : 'No Answer'
            ];

            $wrong_ids[] = $q['id'];
        }
    }

    // Convert wrong IDs to string
    $wrong_ids_str = implode(',', $wrong_ids);

    // Update Database
    if ($attempt_data) {
        $stmt = $conn->prepare("
            UPDATE exam_results 
            SET score = ?, attempts = attempts + 1, wrong_questions = ? 
            WHERE username = ? AND module_id = ?
        ");
        $stmt->bind_param("issi", $score, $wrong_ids_str, $username, $module_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            INSERT INTO exam_results 
            (username, module_id, score, total_questions, attempts, wrong_questions) 
            VALUES (?, ?, ?, ?, 1, ?)
        ");
        $stmt->bind_param("siiis", $username, $module_id, $score, $total, $wrong_ids_str);
        $stmt->execute();
    }
    ?>

<!DOCTYPE html>
<html>
<head>
    <title>Exam Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light p-5">

<div class="container bg-white p-5 shadow rounded" style="max-width: 700px;">

    <h1 class="text-center text-primary">Exam Result</h1>
    <hr>

    <div class="row mb-4">
        <div class="col-6">
            <strong>Name:</strong> <?= htmlspecialchars($full_name) ?>
        </div>
        <div class="col-6 text-end">
            <strong>Attempt:</strong> <?= $current_attempts + 1 ?> / 3
        </div>
    </div>

    <div class="alert <?= ($total > 0 && ($score / $total) >= 0.7) ? 'alert-success' : 'alert-danger' ?> text-center">
        <h2 class="mb-0">Score: <?= $score ?> / <?= $total ?></h2>
    </div>

    <?php if (!empty($mistakes)): ?>
        <div class="mt-4">
            <h5 class="text-danger mb-3">Review Your Mistakes:</h5>

            <?php foreach ($mistakes as $m): ?>
                <div class="mb-3 p-3 border-start border-danger border-4 bg-light">
                    <p class="fw-bold mb-1">
                        <?= htmlspecialchars($m['question']) ?>
                    </p>
                    <p class="text-danger mb-0">
                        <strong>Your Answer:</strong>
                        <?= $m['user_choice'] ?>)
                        <?= htmlspecialchars($m['user_choice_text']) ?>
                    </p>
                </div>
            <?php endforeach; ?>

        </div>
    <?php endif; ?>

    <div class="d-grid mt-5">
        <a href="EMPLOYEE.php" class="btn btn-primary btn-lg">
            Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>

<?php
    exit();
}
// --- END NEW FEATURE ---
?>

<!DOCTYPE html>
<html>

<head>
    <title>Take Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light p-4">

<div class="container bg-white p-4 shadow-sm rounded" style="max-width: 800px;">

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h3>Exam Session</h3>
        <span class="badge bg-dark">Attempt <?= $current_attempts + 1 ?> / 3</span>
    </div>

    <form method="POST">

        <?php foreach ($questions as $idx => $q): ?>

            <div class="mb-4 p-3 border rounded shadow-sm">

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

                        <label class="form-check-label">
                            <?= $opt ?>)
                            <?= htmlspecialchars($q['option_' . strtolower($opt)]) ?>
                        </label>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

        <button type="submit"
                name="submit_exam"
                class="btn btn-success btn-lg w-100 mt-3">
            Submit Exam
        </button>

    </form>

</div>

</body>
</html>