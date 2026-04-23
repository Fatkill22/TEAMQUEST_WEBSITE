<?php
session_start();
include "config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $module_id = (int)$_POST['module_id'];
    $username = $_SESSION['username'];
    
    // 1. Fetch the Admin's Master Key for this specific module
    $ans_query = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
    $correct_map = [];
    while($row = $ans_query->fetch_assoc()) {
        $correct_map[$row['question_num']] = $row['correct_val'];
    }

    // Safety check
    if (count($correct_map) < 50) {
        echo "<script>alert('ERROR: Master Answer Key missing.'); window.location.href = 'EMPLOYEE.php';</script>";
        exit();
    }

    $score = 0;
    $total_questions = 50;
    $wrong_answers = [];

    // 2. Compare Employee's answers against the Master Key
    for ($i = 1; $i <= 50; $i++) {
        $input_name = 'q_' . $i;
        
        // Grab EXACTLY what the user typed
        if (isset($_POST[$input_name]) && $_POST[$input_name] !== '') {
            $user_ans = (int)$_POST[$input_name];
        } else {
            $user_ans = 'Blank'; // Catch empty inputs
        }
        
        $correct_ans = isset($correct_map[$i]) ? (int)$correct_map[$i] : null;

        // Grade it
        if ($user_ans !== 'Blank' && $correct_ans !== null && $user_ans === $correct_ans) {
            $score++;
        } else {
            // NEW: Save the EXACT answer the user typed instead of just the question number
            $wrong_answers[$i] = $user_ans; 
        }
    }

    // Encode mistakes as an exact map: e.g., {"1": 0, "5": 1, "12": "Blank"}
    $wrong_json = json_encode($wrong_answers);

    // 3. Save the score and exact wrong answers
    $stmt = $conn->prepare("INSERT INTO exam_results (username, module_id, score, total_questions, attempts, wrong_questions) 
                            VALUES (?, ?, ?, ?, 1, ?) 
                            ON DUPLICATE KEY UPDATE 
                            score = VALUES(score), attempts = attempts + 1, wrong_questions = VALUES(wrong_questions)");
    $stmt->bind_param("siiis", $username, $module_id, $score, $total_questions, $wrong_json);
    $stmt->execute();

    header("Location: EMPLOYEE.php");
    exit();
}