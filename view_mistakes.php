<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) { header("Location: INDEX.php"); exit(); }

$module_id = (int)$_GET['module_id'];
$username  = $_SESSION['username'];

$res  = $conn->query("SELECT wrong_questions FROM exam_results WHERE username = '$username' AND module_id = $module_id");
$data = $res->fetch_assoc();

if (!$data || empty($data['wrong_questions'])) {
    header("Location: EMPLOYEE.php?tab=results");
    exit();
}

$ids_array = array_filter(explode(',', $data['wrong_questions']), function($id) { return is_numeric($id); });

if (empty($ids_array)) { header("Location: EMPLOYEE.php?tab=results"); exit(); }

$ids       = implode(',', $ids_array);
$questions = $conn->query("SELECT * FROM questions WHERE id IN ($ids)")->fetch_all(MYSQLI_ASSOC);

$mod_res    = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$mod_title  = ($mod_res->fetch_assoc())['title'] ?? 'Module';
$avatar     = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Review Answers — TeamQuest</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="tq-shell">

  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">Review Answers</span>
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
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=gauge" class="tq-nav-link"><i class="bi bi-clipboard-check-fill"></i><span>Gauge Exams</span></a></li>
        <li class="tq-nav-item active"><a href="EMPLOYEE.php?tab=results" class="tq-nav-link"><i class="bi bi-bar-chart-fill"></i><span>My Results</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>

    <main class="tq-content">
      <div style="max-width:780px; margin:0 auto;">

        <div class="tq-section-header"><i class="bi bi-patch-question-fill" style="color:var(--tq-danger);"></i>Review Mistakes — <?= htmlspecialchars($mod_title) ?></div>

        <div class="tq-card" style="margin-bottom:20px; border-left:5px solid var(--tq-warning);">
          <div class="tq-card-body" style="padding:12px 20px; font-size:13px; color:#92400e; font-weight:500;">
            <i class="bi bi-lightbulb-fill me-2" style="color:var(--tq-warning);"></i>
            Study these questions carefully before your next attempt.
            <strong><?= count($questions) ?> mistake<?= count($questions) > 1 ? 's' : '' ?></strong> found.
          </div>
        </div>

        <?php foreach ($questions as $idx => $q): ?>
        <div class="tq-card" style="margin-bottom:14px;">
          <div class="tq-card-body">
            <div class="tq-question-num">Question <?= $idx + 1 ?> of <?= count($questions) ?></div>
            <div class="tq-question-text"><?= htmlspecialchars($q['question_text']) ?></div>
            <ul class="tq-mistake-all-options">
              <?php foreach (['A','B','C','D'] as $opt): ?>
              <li class="<?= $q['correct_option'] === $opt ? 'correct' : '' ?>">
                <?php if ($q['correct_option'] === $opt): ?>
                <i class="bi bi-check-circle-fill me-2" style="color:var(--tq-success);"></i>
                <?php else: ?>
                <i class="bi bi-circle me-2" style="color:#d1d5db;"></i>
                <?php endif; ?>
                <strong><?= $opt ?>)</strong> <?= htmlspecialchars($q['option_' . strtolower($opt)]) ?>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endforeach; ?>

        <div style="display:flex; gap:12px; margin-top:8px;">
          <a href="EMPLOYEE.php?tab=results" class="btn-tq-outline" style="flex:1; justify-content:center; padding:12px;">
            <i class="bi bi-arrow-left"></i> Back to Results
          </a>
          <a href="EMPLOYEE.php?tab=modules" class="btn-tq-primary" style="flex:1; justify-content:center; padding:12px;">
            <i class="bi bi-book"></i> Go to Modules
          </a>
        </div>

      </div>
    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
</body>
</html>
