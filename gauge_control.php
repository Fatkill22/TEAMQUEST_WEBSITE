<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    die("Access Denied");
}

$module_id = (int)$_GET['id'];

// Handle Saving the Master Key
if (isset($_POST['save_master_key'])) {
    for ($i = 1; $i <= 50; $i++) {
        $val = isset($_POST["ans_$i"]) ? (int)$_POST["ans_$i"] : 0;
        $conn->query("INSERT INTO gauge_answers (module_id, question_num, correct_val) 
                      VALUES ($module_id, $i, $val) 
                      ON DUPLICATE KEY UPDATE correct_val = $val");
    }
    echo "<script>alert('Master Key Updated!'); window.location.href='admin_modules.php';</script>";
}

// Fetch existing Master Key
$master_key = [];
$res = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
while($row = $res->fetch_assoc()) {
    $master_key[$row['question_num']] = $row['correct_val'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Master Key Editor - TeamQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .gauge-container { max-width: 500px; margin: auto; border: 2px solid #000; background: #fff; }
        .g-header { border-bottom: 2px solid #000; text-align: center; padding: 10px; font-weight: bold; }
        .g-table { width: 100%; border-collapse: collapse; }
        .g-table td, .g-table th { border: 1px solid #000; padding: 2px; text-align: center; }
        .g-input { width: 100%; border: none; text-align: center; font-weight: bold; background: #fff4f4; }
    </style>
</head>
<body class="bg-light p-4">

<div class="gauge-container shadow">
    <div class="g-header">
        <img src="Images/Logo.png" height="40"><br>
        MASTER ANSWER KEY EDITOR<br>
        <span class="text-danger small">ADMIN USE ONLY</span>
    </div>

    <form method="POST">
        <table class="g-table">
            <thead>
                <tr>
                    <th width="20%">SCORE</th>
                    <th width="80%">MASTER ANSWER (1 or 0)</th>
                </tr>
            </thead>
            <tbody>
                <?php for($i = 1; $i <= 50; $i++): ?>
                <tr>
                    <td><?= $i ?></td>
                    <td>
                        <input type="number" name="ans_<?= $i ?>" class="g-input" 
                               min="0" max="1" value="<?= $master_key[$i] ?? 0 ?>" required>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="p-3 bg-dark sticky-bottom">
            <button type="submit" name="save_master_key" class="btn btn-warning w-100 fw-bold">SAVE MASTER KEY</button>
        </div>
    </form>
</div>

</body>
</html>