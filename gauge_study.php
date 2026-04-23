<?php
session_start();
include "config.php";

$module_id = (int)$_GET['id'];
$mod_res = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$mod_data = $mod_res->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gauge Study Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f0f0; }
        .sheet { max-width: 550px; margin: 20px auto; background: #fff; border: 2px solid #000; }
        .sheet-header { text-align: center; border-bottom: 2px solid #000; padding: 10px; font-weight: bold; }
        .info-row { display: flex; border-bottom: 1px solid #000; font-size: 12px; }
        .info-row div { flex: 1; padding: 5px; border-right: 1px solid #000; }
        .instruction { text-align: center; color: blue; font-size: 13px; font-weight: bold; padding: 5px; border-bottom: 1px solid #000; }
        .g-table { width: 100%; border-collapse: collapse; }
        .g-table td, .g-table th { border: 1px solid #000; text-align: center; height: 28px; }
        .user-input { width: 100%; border: none; text-align: center; outline: none; font-weight: bold; }
        .user-input:focus { background: #eef; }
    </style>
</head>
<body>

<div class="sheet shadow">
    <div class="sheet-header">
        <img src="Images/Logo.png" height="40" class="float-end">
        ATTRIBUTE GAUGE REPEATABILITY<br>AND REPRODUCIBILITY STUDY
    </div>
    
    <div class="info-row">
        <div>Name: <strong><?= $_SESSION['username'] ?></strong></div>
        <div>Date: <?= date('Y-m-d') ?></div>
    </div>
    <div class="info-row">
        <div>Emp. #: ________</div>
        <div>Process: <?= htmlspecialchars($mod_data['title']) ?></div>
    </div>

    <div class="instruction">Put 1 if "good" and 0 for "reject"</div>

    <form action="process_gauge.php" method="POST">
        <input type="hidden" name="module_id" value="<?= $module_id ?>">
        <table class="g-table">
            <thead>
                <tr>
                    <th width="15%">SCORE</th>
                    <th>TRIAL</th>
                    <th width="30%">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                <?php for($i = 1; $i <= 50; $i++): ?>
                <tr>
                    <td class="bg-light"><?= $i ?></td>
                    <td><input type="number" name="q_<?= $i ?>" class="user-input" min="0" max="1" required></td>
                    <td></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        
        <div class="p-3">
            <button type="submit" class="btn btn-primary w-100">SUBMIT EXAM</button>
            <div class="text-center mt-2 small text-muted">AD-0001-F4-Rev 2</div>
        </div>
    </form>
</div>

<script>
    // Autofocus next field for speed
    const inputs = document.querySelectorAll('.user-input');
    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
    });
</script>

</body>
</html>