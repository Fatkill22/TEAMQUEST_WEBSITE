<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) { header("Location: INDEX.php"); exit(); }

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
$username  = $_SESSION['username'];

$mod_res     = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$module_title = ($mod_res->fetch_assoc())['title'] ?? "Gauge Study";

$res  = $conn->query("SELECT score, total_questions, wrong_questions FROM exam_results WHERE username = '$username' AND module_id = $module_id");
$data = $res->fetch_assoc();

if (!$data) { header("Location: EMPLOYEE.php?tab=results"); exit(); }

$mistakes = [];
if (!empty($data['wrong_questions'])) {
    $decoded = json_decode($data['wrong_questions'], true);
    if (is_array($decoded)) { $mistakes = $decoded; }
}

$master_key = [];
$ans_query  = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
while ($row = $ans_query->fetch_assoc()) { $master_key[$row['question_num']] = $row['correct_val']; }

$score   = (int)$data['score'];
$total   = (int)$data['total_questions'];
$percent = $total > 0 ? round(($score / $total) * 100) : 0;
$passed  = $percent >= 70;
$avatar  = strtoupper(substr($username, 0, 1));
$wrong_count = count($mistakes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gauge Results — TeamQuest</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="tq-shell">

  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">Gauge Study Results</span>
    <div class="tq-user-badge">
      <div class="tq-avatar"><?= $avatar ?></div>
      <span><?= htmlspecialchars($username) ?></span>
    </div>
  </header>

  <div class="tq-body">
    <nav class="tq-sidebar">
      <ul class="tq-nav">
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=home" class="tq-nav-link"><i class="bi bi-house-fill"></i><span>Home</span></a></li>
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=modules" class="tq-nav-link"><i class="bi bi-book-fill"></i><span>My Modules</span></a></li>
        <li class="tq-nav-item active"><a href="EMPLOYEE.php?tab=gauge" class="tq-nav-link"><i class="bi bi-clipboard-check-fill"></i><span>Attribute Gauge R&amp;R Study</span></a></li>
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=results" class="tq-nav-link"><i class="bi bi-bar-chart-fill"></i><span>My Results</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>

    <main class="tq-content">
      <div style="max-width:780px; margin:0 auto;">

        <div class="tq-section-header"><i class="bi bi-clipboard-data-fill"></i><?= htmlspecialchars($module_title) ?> — Results</div>

        <!-- Score Card -->
        <div class="tq-result-score-card <?= $passed ? 'passed' : 'failed' ?>" style="margin-bottom:20px;">
          <div style="font-size:13px; color:var(--tq-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Final Score</div>
          <div class="score-num"><?= $score ?> / <?= $total ?></div>
          <div class="score-label"><?= $passed ? '✓ Passed' : '✗ Failed' ?></div>
          <div style="display:flex; justify-content:center; gap:20px; margin-top:12px; font-size:13px; color:var(--tq-muted);">
            <span>Appraiser: <strong style="color:var(--tq-text);"><?= htmlspecialchars($username) ?></strong></span>
            <span>Score: <strong style="color:var(--tq-text);"><?= $percent ?>%</strong></span>
            <span>Mistakes: <strong style="color:var(--tq-danger);"><?= $wrong_count ?></strong></span>
          </div>
        </div>

        <!-- Detail Table -->
        <div class="tq-card">
          <div class="tq-card-header"><i class="bi bi-table me-2" style="color:var(--tq-gold);"></i>Detailed Breakdown</div>
          <div style="overflow-x:auto;">
            <table class="tq-table">
              <thead>
                <tr>
                  <th style="text-align:center; width:60px;">#</th>
                  <th style="text-align:center;">Your Answer</th>
                  <th style="text-align:center;">Result</th>
                </tr>
              </thead>
              <tbody>
                <?php for ($i = 1; $i <= 50; $i++):
                  $correct_ans = $master_key[$i] ?? null;
                  $is_wrong    = array_key_exists($i, $mistakes);
                  $user_ans    = $is_wrong ? $mistakes[$i] : $correct_ans;
                ?>
                <tr style="<?= $is_wrong ? 'background:#fff5f5;' : '' ?>">
                  <td style="text-align:center; font-weight:700; color:var(--tq-navy);"><?= $i ?></td>
                  <td style="text-align:center; font-weight:700; color:<?= $is_wrong ? 'var(--tq-danger)' : 'var(--tq-success)' ?>;">
                    <?= htmlspecialchars($user_ans ?? '—') ?>
                  </td>
                  <td style="text-align:center;">
                    <?php if ($correct_ans === null): ?>
                    <span class="tq-badge tq-badge-gray">Key Missing</span>
                    <?php elseif ($is_wrong): ?>
                    <span class="tq-badge tq-badge-danger"><i class="bi bi-x me-1"></i>Wrong (Correct: <?= $correct_ans ?>)</span>
                    <?php else: ?>
                    <span class="tq-badge tq-badge-success"><i class="bi bi-check me-1"></i>Correct</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div style="display:flex; gap:12px; margin-top:8px;">
          <a href="EMPLOYEE.php?tab=results" class="btn-tq-outline" style="flex:1; justify-content:center; padding:12px;">
            <i class="bi bi-arrow-left"></i> Back to Results
          </a>
          <a href="EMPLOYEE.php?tab=gauge" class="btn-tq-primary" style="flex:1; justify-content:center; padding:12px;">
            <i class="bi bi-clipboard-check"></i> Attribute Gauge R&amp;R Study
          </a>
        </div>

      </div>
    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
</body>
</html>
