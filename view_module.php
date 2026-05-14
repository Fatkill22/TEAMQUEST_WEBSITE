<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: EMPLOYEE.php");
    exit();
}

$id   = $_GET['id'];
$dept = $_SESSION['department'];
$user = $_SESSION['username'];

$stmt = $conn->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$module = $stmt->get_result()->fetch_assoc();

if (!$module) { die("Training module not found."); }

$check_exam = $conn->query("SELECT id FROM questions WHERE module_id = $id LIMIT 1");
$has_exam   = ($check_exam->num_rows > 0);

$check_result = $conn->query("SELECT score, total_questions, attempts FROM exam_results WHERE username = '$user' AND module_id = $id");
$result_row   = ($check_result && $check_result->num_rows > 0) ? $check_result->fetch_assoc() : null;
$exam_done    = $result_row !== null;
$attempts     = $result_row ? (int)$result_row['attempts'] : 0;
$passed       = $result_row && $result_row['total_questions'] > 0 && ($result_row['score'] / $result_row['total_questions']) >= 0.7;

// Step: 1=Watch, 2=Exam ready/Finish, 3=Done
$no_exam_done  = (!$has_exam && $exam_done);
$current_step  = $exam_done ? 3 : ($has_exam ? 2 : 1);

$prev_stmt = $conn->prepare("SELECT id FROM modules WHERE id < ? AND (FIND_IN_SET(?, department) OR department = 'all') AND title NOT LIKE '%Gauge%' ORDER BY id DESC LIMIT 1");
$prev_stmt->bind_param("is", $id, $dept);
$prev_stmt->execute();
$prev_id = ($prev_stmt->get_result()->fetch_assoc())['id'] ?? null;

$next_stmt = $conn->prepare("SELECT id FROM modules WHERE id > ? AND (FIND_IN_SET(?, department) OR department = 'all') AND title NOT LIKE '%Gauge%' ORDER BY id ASC LIMIT 1");
$next_stmt->bind_param("is", $id, $dept);
$next_stmt->execute();
$next_id = ($next_stmt->get_result()->fetch_assoc())['id'] ?? null;

$avatar = strtoupper(substr($user, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($module['title']) ?> — TeamQuest Training</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="tq-shell">

  <!-- TOPBAR -->
  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title"><?= htmlspecialchars($module['title']) ?></span>
    <div class="tq-user-badge">
      <div class="tq-avatar"><?= $avatar ?></div>
      <span><?= htmlspecialchars($user) ?></span>
    </div>
  </header>

  <div class="tq-body">

    <!-- SIDEBAR -->
    <nav class="tq-sidebar">
      <ul class="tq-nav">
        <li class="tq-nav-item">
          <a href="EMPLOYEE.php?tab=home" class="tq-nav-link">
            <i class="bi bi-house-fill"></i><span>Home</span>
          </a>
        </li>
        <li class="tq-nav-item active">
          <a href="EMPLOYEE.php?tab=modules" class="tq-nav-link">
            <i class="bi bi-book-fill"></i><span>My Modules</span>
          </a>
        </li>
        <li class="tq-nav-item">
          <a href="EMPLOYEE.php?tab=gauge" class="tq-nav-link">
            <i class="bi bi-clipboard-check-fill"></i><span>Gauge Exams</span>
          </a>
        </li>
        <li class="tq-nav-item">
          <a href="EMPLOYEE.php?tab=results" class="tq-nav-link">
            <i class="bi bi-bar-chart-fill"></i><span>My Results</span>
          </a>
        </li>
      </ul>
      <div class="tq-sidebar-footer">
        <a href="LOGOUT.php" class="tq-logout">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="tq-content" style="padding:0;">

      <!-- Hero Header -->
      <div class="tq-module-hero">
        <div style="max-width:800px; margin:0 auto; padding:0 24px;">
          <h1><?= htmlspecialchars($module['title']) ?></h1>
          <p><i class="bi bi-building me-1"></i>Department: <?= htmlspecialchars($module['department']) ?></p>
          <!-- Step indicator in hero -->
          <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.12); padding:8px 20px; border-radius:20px; margin-top:12px; font-size:12px;">
            <span style="<?= $current_step >= 1 ? 'color:#e0a800; font-weight:700;' : 'color:rgba(255,255,255,0.5);' ?>">
              <i class="bi bi-<?= $current_step > 1 ? 'check-circle-fill' : 'play-circle' ?> me-1"></i>① Watch
            </span>
            <span style="color:rgba(255,255,255,0.3); margin:0 4px;">›</span>
            <span style="<?= $current_step >= 2 ? 'color:#e0a800; font-weight:700;' : 'color:rgba(255,255,255,0.5);' ?>">
              <i class="bi bi-<?= $current_step > 2 ? 'check-circle-fill' : 'pencil-square' ?> me-1"></i>② Exam
            </span>
            <span style="color:rgba(255,255,255,0.3); margin:0 4px;">›</span>
            <span style="<?= $current_step >= 3 ? 'color:#4ade80; font-weight:700;' : 'color:rgba(255,255,255,0.5);' ?>">
              <i class="bi bi-<?= $current_step >= 3 ? 'check-circle-fill' : 'bar-chart' ?> me-1"></i>③ Results
            </span>
          </div>
        </div>
      </div>

      <!-- Content Card -->
      <div style="max-width:900px; margin:0 auto; padding:0 24px 48px;">
        <div class="tq-content-card">

          <a href="EMPLOYEE.php?tab=modules" class="btn-tq-outline" style="margin-bottom:24px;">
            <i class="bi bi-arrow-left"></i> Back to Modules
          </a>

          <!-- Description -->
          <div style="margin-bottom:28px;">
            <div style="font-size:11px; font-weight:700; color:var(--tq-gold); text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">Introduction</div>
            <p style="font-size:15px; line-height:1.75; color:var(--tq-text);"><?= nl2br(htmlspecialchars($module['description'])) ?></p>
          </div>

          <?php if (!empty($module['content'])): ?>
          <div class="tq-divider"></div>
          <div style="margin-bottom:28px;">
            <div style="font-size:11px; font-weight:700; color:var(--tq-gold); text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">Module Information</div>
            <div style="font-size:14px; line-height:1.8; color:#333;"><?= nl2br(htmlspecialchars($module['content'])) ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($module['video'])): ?>
          <div class="tq-video-box">
            <video width="100%" controls controlsList="nodownload">
              <source src="Videos/<?= htmlspecialchars($module['video']) ?>" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          </div>
          <?php endif; ?>

          <!-- Navigation Buttons -->
          <div style="display:flex; justify-content:space-between; align-items:center; margin-top:40px; padding-top:28px; border-top:2px solid #eef0f7;">
            <div>
              <?php if ($prev_id): ?>
              <a href="view_module.php?id=<?= $prev_id ?>" class="btn-tq-outline">
                <i class="bi bi-arrow-left"></i> Previous
              </a>
              <?php endif; ?>
            </div>

            <div style="text-align:center;">
              <?php if (!$has_exam): ?>
                <?php if ($no_exam_done): ?>
                <span class="tq-badge tq-badge-success" style="padding:10px 20px; font-size:13px;">
                  <i class="bi bi-check-circle-fill me-1"></i> Module Completed
                </span>
                <?php else: ?>
                <form method="POST" action="process_finish_module.php" style="display:inline;">
                  <input type="hidden" name="module_id" value="<?= $id ?>">
                  <?php if ($next_id): ?><input type="hidden" name="next_id" value="<?= $next_id ?>"><?php endif; ?>
                  <button type="submit" class="btn-tq-gold" style="padding:12px 32px; font-size:15px;">
                    <i class="bi bi-check-circle"></i> Finish Module
                  </button>
                </form>
                <?php endif; ?>
              <?php elseif ($attempts >= 3): ?>
              <span class="tq-badge tq-badge-danger" style="padding:10px 20px; font-size:13px;">
                <i class="bi bi-lock-fill me-1"></i> Max Attempts Reached
              </span>
              <div style="margin-top:10px; font-size:13px; color:var(--tq-text); font-weight:600;">
                Best score: <?= $result_row['score'] ?> / <?= $result_row['total_questions'] ?>
                <span style="color:<?= $passed ? 'var(--tq-success)' : 'var(--tq-danger)' ?>; margin-left:6px;">
                  (<?= $passed ? 'Passed' : 'Failed' ?>)
                </span>
              </div>
              <?php else: ?>
              <a href="take_exam.php?module_id=<?= $id ?>" class="btn-tq-gold" style="padding:12px 32px; font-size:15px;">
                <i class="bi bi-pencil-square"></i>
                <?= $exam_done ? 'Retake Exam' : 'Take Exam' ?>
              </a>
              <?php if ($exam_done): ?>
              <div style="margin-top:10px; font-size:12px; color:var(--tq-muted);">
                Best score so far: <?= $result_row['score'] ?> / <?= $result_row['total_questions'] ?> —
                <span style="color:<?= $passed ? 'var(--tq-success)' : 'var(--tq-danger)' ?>; font-weight:600;"><?= $passed ? 'Passed' : 'Failed' ?></span>
              </div>
              <?php endif; ?>
              <?php endif; ?>
            </div>

            <div>
              <?php if ($next_id): ?>
                <?php if (!$has_exam && !$no_exam_done): ?>
                <form method="POST" action="process_finish_module.php" style="display:inline;">
                  <input type="hidden" name="module_id" value="<?= $id ?>">
                  <input type="hidden" name="next_id" value="<?= $next_id ?>">
                  <button type="submit" class="btn-tq-outline">
                    Next <i class="bi bi-arrow-right"></i>
                  </button>
                </form>
                <?php else: ?>
                <a href="view_module.php?id=<?= $next_id ?>" class="btn-tq-outline">
                  Next <i class="bi bi-arrow-right"></i>
                </a>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>

    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
</body>
</html>
