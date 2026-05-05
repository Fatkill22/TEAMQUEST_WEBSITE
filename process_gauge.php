<?php
session_start();
include "config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $module_id = (int)$_POST['module_id'];
    $username = $_SESSION['username'];
    
    // STRICT ATTEMPT LIMIT CHECK
    $att_check = $conn->query("SELECT attempts FROM exam_results WHERE username = '$username' AND module_id = $module_id");
    if ($att_check && $att_check->num_rows > 0) {
        if ($att_check->fetch_assoc()['attempts'] >= 3) {
            header("Location: EMPLOYEE.php?tab=gauge&error=locked");
            exit();
        }
    }

    // Fetch the Admin's Master Key
    $ans_query = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
    $correct_map = [];
    while($row = $ans_query->fetch_assoc()) {
        $correct_map[$row['question_num']] = $row['correct_val'];
    }

    // Safety check for empty Answer Key
    if (count($correct_map) < 50) {
        header("Location: EMPLOYEE.php?tab=gauge&error=nokey");
        exit();
    }

    $score = 0;
    $total_questions = 50;
    $wrong_answers = [];

    // Grade and save exactly what the user typed
    for ($i = 1; $i <= 50; $i++) {
        $input_name = 'q_' . $i;
        $user_ans = (isset($_POST[$input_name]) && $_POST[$input_name] !== '') ? (int)$_POST[$input_name] : 'Blank';
        $correct_ans = isset($correct_map[$i]) ? (int)$correct_map[$i] : null;

        if ($user_ans !== 'Blank' && $correct_ans !== null && $user_ans === $correct_ans) {
            $score++;
        } else {
            $wrong_answers[$i] = $user_ans; 
        }
    }

    $wrong_json = json_encode($wrong_answers);

    // Store full per-attempt answers for Excel export
    $full_answers = [];
    for ($i = 1; $i <= 50; $i++) {
        $key = 'q_' . $i;
        $full_answers[$i] = (isset($_POST[$key]) && $_POST[$key] !== '') ? (int)$_POST[$key] : 0;
    }
    $full_json = json_encode($full_answers);
    $att_cnt_res = $conn->query("SELECT COUNT(*) as cnt FROM gauge_attempt_details WHERE username = '" . $conn->real_escape_string($username) . "' AND module_id = $module_id");
    $attempt_num = ($att_cnt_res ? (int)$att_cnt_res->fetch_assoc()['cnt'] : 0) + 1;
    $stmt_det = $conn->prepare("INSERT IGNORE INTO gauge_attempt_details (username, module_id, attempt_num, answers_json) VALUES (?, ?, ?, ?)");
    $stmt_det->bind_param("siis", $username, $module_id, $attempt_num, $full_json);
    $stmt_det->execute();

    // Save
    $stmt = $conn->prepare("INSERT INTO exam_results (username, module_id, score, total_questions, attempts, wrong_questions)
                            VALUES (?, ?, ?, ?, 1, ?)
                            ON DUPLICATE KEY UPDATE score = VALUES(score), attempts = attempts + 1, wrong_questions = VALUES(wrong_questions)");
    $stmt->bind_param("siiis", $username, $module_id, $score, $total_questions, $wrong_json);
    $stmt->execute();

    header("Location: view_gauge_mistakes.php?module_id=" . $module_id);
    exit();
}