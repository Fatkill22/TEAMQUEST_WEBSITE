<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) { header("Location: INDEX.php"); exit(); }

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
$username = $_SESSION['username'];

// Fetch the module title
$mod_res = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$module_title = ($mod_res->fetch_assoc())['title'] ?? "Gauge Study";

// Fetch the saved wrong questions and score
$res = $conn->query("SELECT score, total_questions, wrong_questions FROM exam_results WHERE username = '$username' AND module_id = $module_id");
$data = $res->fetch_assoc();

if (!$data) {
    die("<div class='container mt-5 text-center'><h3>No results found!</h3><a href='EMPLOYEE.php' class='btn btn-primary'>Back</a></div>");
}

// Decode exact mistakes array
$mistakes = [];
if (!empty($data['wrong_questions'])) {
    $decoded = json_decode($data['wrong_questions'], true);
    if (is_array($decoded)) {
        $mistakes = $decoded;
    }
}

// Fetch the Admin Master Key
$master_key = [];
$ans_query = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
while($row = $ans_query->fetch_assoc()) {
    $master_key[$row['question_num']] = $row['correct_val'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gauge Results - TeamQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e9ecef; font-family: Arial, sans-serif; }
        .sheet { max-width: 700px; margin: 30px auto; background: #fff; border: 2px solid #000; position: relative; }
        .sheet-header { text-align: center; border-bottom: 2px solid #000; padding: 15px; font-weight: bold; font-size: 18px; }
        .sheet-logo { position: absolute; right: 15px; top: 10px; height: 45px; }
        .info-table { width: 100%; border-bottom: 2px solid #000; }
        .info-table td { border: 1px solid #000; padding: 5px 10px; font-size: 14px; width: 50%; }
        .instruction { text-align: center; color: blue; font-size: 14px; font-weight: bold; padding: 5px; border-bottom: 2px solid #000; }
        .g-table { width: 100%; border-collapse: collapse; }
        .g-table td, .g-table th { border: 1px solid #000; text-align: center; height: 28px; font-size: 14px; }
        .g-table th { background: #fff; text-transform: uppercase; }
        .correct-row { background-color: #f8fff8; }
        .wrong-row { background-color: #fff4f4; }
        .return-btn { position: fixed; bottom: 20px; right: 20px; z-index: 1000; }
    </style>
</head>
<body>

<a href="EMPLOYEE.php" class="btn btn-dark btn-lg shadow fw-bold return-btn">BACK TO DASHBOARD</a>

<div class="container pb-5">
    <div class="sheet shadow-lg">
        <div class="sheet-header">
            ATTRIBUTE GAUGE REPEATABILITY<br>AND REPRODUCIBILITY STUDY
            <img src="Images/Logo.png" class="sheet-logo" alt="TeamQuest">
        </div>
        
        <table class="info-table">
            <tr>
                <td>Name: <strong><?= htmlspecialchars($username) ?></strong></td>
                <td>Date Evaluated: <strong><?= date('Y-m-d') ?></strong></td>
            </tr>
            <tr>
                <td>Emp. #: ________________</td>
                <td>Process: <strong><?= htmlspecialchars($module_title) ?></strong></td>
            </tr>
            <tr>
                <td colspan="2" class="text-center bg-light">
                    Final Score: <strong class="fs-5 text-<?= $data['score'] >= 35 ? 'success' : 'danger' ?>"><?= $data['score'] ?> / <?= $data['total_questions'] ?></strong>
                </td>
            </tr>
        </table>

        <div class="instruction">Put 1 if "good" and 0 for "reject"</div>

        <table class="g-table">
            <thead>
                <tr>
                    <th width="15%">SCORE</th>
                    <th width="35%">TRIAL (Your Answer)</th>
                    <th width="50%">REMARKS (Checker)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                for($i = 1; $i <= 50; $i++): 
                    $correct_ans = isset($master_key[$i]) ? $master_key[$i] : null;

                    // NEW LOGIC: Check exactly what was saved in the database
                    $is_wrong = array_key_exists($i, $mistakes);
                    
                    if ($is_wrong) {
                        $user_ans = $mistakes[$i]; // Pull exact wrong answer typed by user
                    } else {
                        $user_ans = $correct_ans;  // If they weren't wrong, they matched the master key
                    }

                    $row_class = $is_wrong ? 'wrong-row' : 'correct-row';
                ?>
                <tr class="<?= $row_class ?>">
                    <td class="fw-bold"><?= $i ?></td>
                    <td class="fw-bold <?= $is_wrong ? 'text-danger' : 'text-success' ?>"><?= htmlspecialchars($user_ans) ?></td>
                    <td>
                        <?php if ($correct_ans === null): ?>
                            <span class="text-muted small">Key missing</span>
                        <?php elseif ($is_wrong): ?>
                            <span class="text-danger fw-bold">❌ Reject (Correct was <?= $correct_ans ?>)</span>
                        <?php else: ?>
                            <span class="text-success fw-bold">✔️ Good</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>