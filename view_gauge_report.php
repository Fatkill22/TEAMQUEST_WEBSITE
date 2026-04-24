<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') { die("Access Denied. Admins only."); }

$module_id = (int)$_GET['id'];
$mod_query = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$module_title = ($mod_query->fetch_assoc())['title'] ?? "Unknown Study";

$key_query = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
$master_key = [];
$total_good = 0; $total_bad = 0;  
while($row = $key_query->fetch_assoc()) {
    $master_key[$row['question_num']] = $row['correct_val'];
    if ($row['correct_val'] == 1) $total_good++;
    if ($row['correct_val'] == 0) $total_bad++;
}

$results_query = $conn->query("SELECT username, score, wrong_questions FROM exam_results WHERE module_id = $module_id ORDER BY score DESC");

function getEffectivenessColor($val) { return $val >= 90 ? 'text-success fw-bold' : ($val >= 80 ? 'text-warning text-dark fw-bold' : 'text-danger fw-bold'); }
function getMissColor($val) { return $val <= 2 ? 'text-success fw-bold' : ($val <= 5 ? 'text-warning text-dark fw-bold' : 'text-danger fw-bold'); }
function getFalseAlarmColor($val) { return $val <= 5 ? 'text-success fw-bold' : ($val <= 10 ? 'text-warning text-dark fw-bold' : 'text-danger fw-bold'); }
function getResultText($type, $val) {
    if ($type == 'eff') return $val >= 90 ? 'Acceptable' : ($val >= 80 ? 'Marginal' : 'Unacceptable');
    if ($type == 'miss') return $val <= 2 ? 'Acceptable' : ($val <= 5 ? 'Marginal' : 'Unacceptable');
    if ($type == 'alarm') return $val <= 5 ? 'Acceptable' : ($val <= 10 ? 'Marginal' : 'Unacceptable');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attribute GR&R Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .report-header { background: #212529; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .table-custom th, .table-custom td { border: 1px solid #dee2e6; text-align: center; vertical-align: middle; font-size: 14px; }
        .table-custom th { background: #e9ecef; }
        .criteria-table th { background: #d1ecf1; }
        .modal-table th { position: sticky; top: 0; background: #212529; color: white; z-index: 10; }
    </style>
</head>
<body class="p-4">
<div class="container-fluid bg-white shadow-sm p-0 rounded">
    <div class="report-header d-flex justify-content-between align-items-center">
        <div><h4 class="m-0">Attribute Measurement System Analysis (GR&R)</h4><small>Process: <?= htmlspecialchars($module_title) ?></small></div>
        <div><span class="badge bg-light text-dark me-2">Good Parts: <?= $total_good ?></span><span class="badge bg-light text-dark">Bad Parts: <?= $total_bad ?></span></div>
    </div>
    <div class="p-4">
        <?php if ($total_good == 0 && $total_bad == 0): ?>
            <div class="alert alert-danger">Master Answer Key not set.</div>
        <?php elseif ($results_query->num_rows == 0): ?>
            <div class="alert alert-info">No appraisers have taken this study yet.</div>
        <?php else: ?>
            <div class="table-responsive mb-5">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr><th rowspan="2">Appraiser</th><th rowspan="2">Raw Score</th><th colspan="3">Calculated Metrics</th><th colspan="3">Analysis</th></tr>
                        <tr><th>Effectiveness</th><th>Miss Rate</th><th>False Alarm</th><th>Eff.</th><th>Miss</th><th>Alarm</th></tr>
                    </thead>
                    <tbody>
                        <?php $modals_html = ""; while ($row = $results_query->fetch_assoc()): 
                            $score = $row['score']; $username = $row['username']; $username_safe = preg_replace('/[^a-zA-Z0-9]/', '', $username);
                            $mistakes = json_decode($row['wrong_questions'], true);
                            $miss_list = []; $false_alarm_list = [];
                            if (is_array($mistakes)) {
                                foreach ($mistakes as $q_num => $user_ans) {
                                    if ($user_ans == 1) $miss_list[] = $q_num; 
                                    if ($user_ans == 0) $false_alarm_list[] = $q_num;
                                }
                            }
                            $miss_count = count($miss_list); $false_alarm_count = count($false_alarm_list); $correct_count = 50 - ($miss_count + $false_alarm_count);
                            $effectiveness = ($score / 50) * 100;
                            $miss_rate = ($total_bad > 0) ? ($miss_count / $total_bad) * 100 : 0;
                            $false_alarm_rate = ($total_good > 0) ? ($false_alarm_count / $total_good) * 100 : 0;
                        ?>
                        <tr>
                            <td class="fw-bold text-start"><?= htmlspecialchars($username) ?></td>
                            <td><span class="d-block fs-6"><?= $score ?> / 50</span><button class="btn btn-sm btn-outline-primary mt-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal_<?= $username_safe ?>">View Breakdown</button></td>
                            <td class="<?= getEffectivenessColor($effectiveness) ?>"><?= number_format($effectiveness, 1) ?>%</td>
                            <td class="<?= getMissColor($miss_rate) ?>"><?= number_format($miss_rate, 1) ?>%</td>
                            <td class="<?= getFalseAlarmColor($false_alarm_rate) ?>"><?= number_format($false_alarm_rate, 1) ?>%</td>
                            <td><span class="badge bg-<?= getResultText('eff', $effectiveness)=='Acceptable'?'success':(getResultText('eff', $effectiveness)=='Marginal'?'warning':'danger') ?>"><?= getResultText('eff', $effectiveness) ?></span></td>
                            <td><span class="badge bg-<?= getResultText('miss', $miss_rate)=='Acceptable'?'success':(getResultText('miss', $miss_rate)=='Marginal'?'warning':'danger') ?>"><?= getResultText('miss', $miss_rate) ?></span></td>
                            <td><span class="badge bg-<?= getResultText('alarm', $false_alarm_rate)=='Acceptable'?'success':(getResultText('alarm', $false_alarm_rate)=='Marginal'?'warning':'danger') ?>"><?= getResultText('alarm', $false_alarm_rate) ?></span></td>
                        </tr>
                        <?php ob_start(); ?>
                        <div class="modal fade" id="modal_<?= $username_safe ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-dark text-white"><h5 class="modal-title m-0">Detailed Breakdown: <?= htmlspecialchars($username) ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body bg-light">
                                        <div class="d-flex justify-content-between mb-3 bg-white p-3 border rounded shadow-sm">
                                            <div class="text-success fw-bold">✔️ Correct: <?= $correct_count ?></div><div class="text-danger fw-bold">❌ Misses: <?= $miss_count ?></div><div class="text-warning text-dark fw-bold">⚠️ False Alarms: <?= $false_alarm_count ?></div>
                                        </div>
                                        <table class="table table-bordered table-hover text-center bg-white modal-table shadow-sm">
                                            <thead><tr><th>Trial #</th><th>Master Key</th><th>User Answer</th><th>Status</th></tr></thead>
                                            <tbody>
                                                <?php for($i = 1; $i <= 50; $i++): 
                                                    $master_ans = isset($master_key[$i]) ? $master_key[$i] : '-';
                                                    $user_ans = $master_ans; $status = "✔️ Correct"; $text_class = "text-success";
                                                    if (in_array($i, $miss_list)) { $user_ans = 1; $status = "❌ MISS"; $text_class = "text-danger fw-bold"; }
                                                    elseif (in_array($i, $false_alarm_list)) { $user_ans = 0; $status = "⚠️ FALSE ALARM"; $text_class = "text-warning text-dark fw-bold"; }
                                                ?>
                                                <tr><td class="fw-bold bg-light"><?= $i ?></td><td class="fw-bold"><?= $master_ans ?></td><td class="<?= $text_class ?> fs-5"><?= $user_ans ?></td><td class="<?= $text_class ?>"><?= $status ?></td></tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
                                </div>
                            </div>
                        </div>
                        <?php $modals_html .= ob_get_clean(); endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?= $modals_html ?>
        <?php endif; ?>
        <a href="admin_modules.php" class="btn btn-dark shadow">← Back to Admin Panel</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>