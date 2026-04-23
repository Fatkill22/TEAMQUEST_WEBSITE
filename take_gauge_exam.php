<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$username = $_SESSION['username'];

// Fetch module details
$mod_query = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$module = $mod_query->fetch_assoc();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attribute Gauge Study - <?= htmlspecialchars($module['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .exam-sheet {
            background-color: white;
            border: 2px solid #333;
            max-width: 600px;
            margin: 20px auto;
            padding: 0;
        }
        .header-box {
            border-bottom: 2px solid #333;
            padding: 10px;
            text-align: center;
            background-color: #fff;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 2px solid #333;
        }
        .info-item {
            padding: 5px 10px;
            border: 1px solid #333;
            font-size: 0.9rem;
        }
        .instruction {
            background-color: #eee;
            padding: 5px;
            text-align: center;
            font-weight: bold;
            font-size: 0.85rem;
            border-bottom: 2px solid #333;
        }
        .gauge-table {
            width: 100%;
            border-collapse: collapse;
        }
        .gauge-table th, .gauge-table td {
            border: 1px solid #333;
            padding: 4px;
            text-align: center;
        }
        .gauge-table th { background-color: #f8f9fa; font-size: 0.8rem; }
        .input-cell {
            width: 100%;
            border: none;
            text-align: center;
            font-weight: bold;
            outline: none;
        }
        .input-cell:focus { background-color: #fff3cd; }
        .sticky-footer {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 15px;
            border-top: 2px solid #333;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <form action="process_gauge.php" method="POST">
        <input type="hidden" name="module_id" value="<?= $module_id ?>">
        
        <div class="exam-sheet shadow-lg">
            <div class="header-box">
                <h5 class="mb-0">ATTRIBUTE GAUGE REPEATABILITY</h5>
                <h6>AND REPRODUCIBILITY STUDY</h6>
            </div>

            <div class="info-grid">
                <div class="info-item">Name: <strong><?= $_SESSION['username'] ?></strong></div>
                <div class="info-item">Date: <?= date('Y-m-d') ?></div>
                <div class="info-item">Emp. #: _______</div>
                <div class="info-item">Process: <?= htmlspecialchars($module['title']) ?></div>
            </div>

            <div class="instruction">
                Put 1 if "good" and 0 for "reject"
            </div>

            <table class="gauge-table">
                <thead>
                    <tr>
                        <th width="20%">SCORE</th>
                        <th width="40%">TRIAL 1</th>
                        <th width="40%">TRIAL 2</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for($i = 1; $i <= 50; $i++): ?>
                    <tr>
                        <td class="fw-bold"><?= $i ?></td>
                        <td>
                            <input type="number" name="q<?= $i ?>_t1" class="input-cell" 
                                   min="0" max="1" required placeholder="0/1">
                        </td>
                        <td>
                            <input type="number" name="q<?= $i ?>_t2" class="input-cell" 
                                   min="0" max="1" required placeholder="0/1">
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <div class="sticky-footer">
                <button type="submit" class="btn btn-primary w-100 fw-bold">SUBMIT STUDY RESULTS</button>
                <small class="text-muted d-block mt-2">AD-0001-F4-Rev 2</small>
            </div>
        </div>
    </form>
</div>

</body>
</html>