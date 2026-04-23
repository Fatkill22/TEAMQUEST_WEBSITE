<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    die("Access Denied. Admins only.");
}

$module_id = (int)$_GET['id'];

// Fetch Module Title
$mod_query = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$module_title = ($mod_query->fetch_assoc())['title'] ?? "Unknown Study";

// 1. Calculate the base metrics from the Master Answer Key
// Find out exactly how many total Good Parts (1) and Bad Parts (0) exist in this exam
$key_query = $conn->query("SELECT correct_val FROM gauge_answers WHERE module_id = $module_id");
$total_good = 0; // Total 1s
$total_bad = 0;  // Total 0s

while($row = $key_query->fetch_assoc()) {
    if ($row['correct_val'] == 1) $total_good++;
    if ($row['correct_val'] == 0) $total_bad++;
}

// Fetch all exam results for this module
$results_query = $conn->query("SELECT username, score, total_questions, wrong_questions FROM exam_results WHERE module_id = $module_id ORDER BY score DESC");

// Helper function to colorize results based on Excel Guidelines
function getEffectivenessColor($val) {
    if ($val >= 90) return 'text-success fw-bold';
    if ($val >= 80) return 'text-warning fw-bold';
    return 'text-danger fw-bold';
}
function getMissColor($val) {
    if ($val <= 2) return 'text-success fw-bold';
    if ($val <= 5) return 'text-warning fw-bold';
    return 'text-danger fw-bold';
}
function getFalseAlarmColor($val) {
    if ($val <= 5) return 'text-success fw-bold';
    if ($val <= 10) return 'text-warning fw-bold';
    return 'text-danger fw-bold';
}
function getResultText($type, $val) {
    if ($type == 'eff') {
        if ($val >= 90) return 'Acceptable';
        if ($val >= 80) return 'Marginal Acceptable';
        return 'Unacceptable';
    }
    if ($type == 'miss') {
        if ($val <= 2) return 'Acceptable';
        if ($val <= 5) return 'Marginal Acceptable';
        return 'Unacceptable';
    }
    if ($type == 'alarm') {
        if ($val <= 5) return 'Acceptable';
        if ($val <= 10) return 'Marginal Acceptable';
        return 'Unacceptable';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attribute GR&R Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .report-header { background: #212529; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .table-custom th { background: #e9ecef; border: 1px solid #dee2e6; text-align: center; vertical-align: middle; }
        .table-custom td { border: 1px solid #dee2e6; text-align: center; vertical-align: middle; }
        .criteria-table th { background: #d1ecf1; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid bg-white shadow-sm p-0 rounded">
    <div class="report-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="m-0">Attribute Measurement System Analysis (GR&R)</h4>
            <small>Process Name: <?= htmlspecialchars($module_title) ?></small>
        </div>
        <div>
            <span class="badge bg-light text-dark me-2">Total Good Parts: <?= $total_good ?></span>
            <span class="badge bg-light text-dark">Total Bad Parts: <?= $total_bad ?></span>
        </div>
    </div>

    <div class="p-4">
        <h5 class="mb-3 fw-bold">Effectiveness Summary</h5>
        
        <?php if ($total_good == 0 && $total_bad == 0): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> Master Answer Key has not been set for this Gauge Study. Calculations cannot be performed.
            </div>
        <?php elseif ($results_query->num_rows == 0): ?>
            <div class="alert alert-info">No appraisers (employees) have taken this study yet.</div>
        <?php else: ?>
            <div class="table-responsive mb-5">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th rowspan="2">Appraiser</th>
                            <th rowspan="2">Raw Score</th>
                            <th colspan="3">Calculated Metrics</th>
                            <th colspan="3">Result Analysis</th>
                        </tr>
                        <tr>
                            <th>Effectiveness<br><small class="text-muted">(Score / Total)</small></th>
                            <th>Miss Rate<br><small class="text-muted">(Accepted Bad Parts)</small></th>
                            <th>False Alarm Rate<br><small class="text-muted">(Rejected Good Parts)</small></th>
                            <th>Effectiveness</th>
                            <th>Miss Rate</th>
                            <th>False Alarm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $results_query->fetch_assoc()): 
                            
                            $score = $row['score'];
                            $mistakes = json_decode($row['wrong_questions'], true);
                            
                            $miss_count = 0;
                            $false_alarm_count = 0;

                            // Calculate Errors
                            if (is_array($mistakes)) {
                                foreach ($mistakes as $q_num => $user_ans) {
                                    // User said 1 (Good), but it was actually Bad -> MISS
                                    if ($user_ans == 1) {
                                        $miss_count++;
                                    }
                                    // User said 0 (Bad), but it was actually Good -> FALSE ALARM
                                    if ($user_ans == 0) {
                                        $false_alarm_count++;
                                    }
                                }
                            }

                            // Math Logic (Matching Excel)
                            $effectiveness = ($score / 50) * 100;
                            $miss_rate = ($total_bad > 0) ? ($miss_count / $total_bad) * 100 : 0;
                            $false_alarm_rate = ($total_good > 0) ? ($false_alarm_count / $total_good) * 100 : 0;
                        ?>
                        <tr>
                            <td class="fw-bold text-start"><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= $score ?> / 50</td>
                            <td class="<?= getEffectivenessColor($effectiveness) ?>"><?= number_format($effectiveness, 1) ?>%</td>
                            <td class="<?= getMissColor($miss_rate) ?>"><?= number_format($miss_rate, 1) ?>%</td>
                            <td class="<?= getFalseAlarmColor($false_alarm_rate) ?>"><?= number_format($false_alarm_rate, 1) ?>%</td>
                            
                            <td><span class="badge bg-<?= getResultText('eff', $effectiveness) == 'Acceptable' ? 'success' : (getResultText('eff', $effectiveness) == 'Marginal Acceptable' ? 'warning' : 'danger') ?>"><?= getResultText('eff', $effectiveness) ?></span></td>
                            <td><span class="badge bg-<?= getResultText('miss', $miss_rate) == 'Acceptable' ? 'success' : (getResultText('miss', $miss_rate) == 'Marginal Acceptable' ? 'warning' : 'danger') ?>"><?= getResultText('miss', $miss_rate) ?></span></td>
                            <td><span class="badge bg-<?= getResultText('alarm', $false_alarm_rate) == 'Acceptable' ? 'success' : (getResultText('alarm', $false_alarm_rate) == 'Marginal Acceptable' ? 'warning' : 'danger') ?>"><?= getResultText('alarm', $false_alarm_rate) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h5 class="mb-3 fw-bold">Criteria Guidelines</h5>
        <div class="table-responsive">
            <table class="table table-bordered text-center criteria-table">
                <thead>
                    <tr>
                        <th>Decision on Measurement System</th>
                        <th>Effectiveness</th>
                        <th>Miss Rate</th>
                        <th>False Alarm Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-start fw-bold text-success">Acceptable for the appraiser</td>
                        <td>≥ 90%</td>
                        <td>≤ 2%</td>
                        <td>≤ 5%</td>
                    </tr>
                    <tr>
                        <td class="text-start fw-bold text-warning">Marginally acceptable - may need improvement</td>
                        <td>80% - 89%</td>
                        <td>2.1% - 5%</td>
                        <td>5.1% - 10%</td>
                    </tr>
                    <tr>
                        <td class="text-start fw-bold text-danger">Unacceptable - needs improvement</td>
                        <td>< 80%</td>
                        <td>> 5%</td>
                        <td>> 10%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <a href="admin_modules.php" class="btn btn-dark">← Back to Admin Panel</a>
        </div>
    </div>
</div>

</body>
</html>