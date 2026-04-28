<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.php");
    exit();
}

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

if (isset($_POST['add_question']) || (isset($_POST['_action']) && $_POST['_action'] === 'add_question')) {
    $q_text  = $_POST['question_text'];
    $a       = $_POST['option_a'];
    $b       = $_POST['option_b'];
    $c       = $_POST['option_c'];
    $d       = $_POST['option_d'];
    $correct = $_POST['correct_option'];

    $stmt = $conn->prepare("INSERT INTO questions (module_id,question_text,option_a,option_b,option_c,option_d,correct_option) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("issssss", $module_id, $q_text, $a, $b, $c, $d, $correct);
    $stmt->execute();
    header("Location: manage_exam.php?module_id=$module_id&added=1");
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM questions WHERE id=$id");
    header("Location: manage_exam.php?module_id=$module_id&deleted=1");
    exit();
}

$questions   = $conn->query("SELECT * FROM questions WHERE module_id = $module_id")->fetch_all(MYSQLI_ASSOC);
$module_info = $conn->query("SELECT title FROM modules WHERE id = $module_id")->fetch_assoc();
$mod_title   = $module_info ? $module_info['title'] : 'Module';
$admin_user  = $_SESSION['username'];
$avatar      = strtoupper(substr($admin_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Exam — <?= htmlspecialchars($mod_title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="tq-shell">

  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">Manage Exam</span>
    <div class="tq-user-badge">
      <div class="tq-avatar"><?= $avatar ?></div>
      <span>Admin: <?= htmlspecialchars($admin_user) ?></span>
    </div>
  </header>

  <div class="tq-body">
    <nav class="tq-sidebar">
      <ul class="tq-nav">
        <li class="tq-nav-item"><a href="admin_modules.php?tab=home" class="tq-nav-link"><i class="bi bi-house-fill"></i><span>Admin Home</span></a></li>
        <li class="tq-nav-item active"><a href="admin_modules.php?tab=admin" class="tq-nav-link"><i class="bi bi-layers-fill"></i><span>Manage Modules</span></a></li>
        <li class="tq-nav-item"><a href="admin_modules.php?tab=gauge-admin" class="tq-nav-link"><i class="bi bi-clipboard-data-fill"></i><span>Gauge Study</span></a></li>
        <li class="tq-nav-item"><a href="admin_modules.php?tab=users" class="tq-nav-link"><i class="bi bi-people-fill"></i><span>User Attempts</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>

    <main class="tq-content">
      <div style="max-width:860px; margin:0 auto;">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
          <div class="tq-section-header" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
            <i class="bi bi-question-circle-fill"></i>Manage Exam — <?= htmlspecialchars($mod_title) ?>
          </div>
          <a href="admin_modules.php?tab=admin" class="btn-tq-outline">
            <i class="bi bi-arrow-left"></i> Back
          </a>
        </div>

        <!-- Collapsible Add Form -->
        <button class="tq-collapse-toggle" id="tq-collapse-btn" type="button">
          <i class="bi bi-chevron-down"></i>
          <span class="btn-label">Add New Question</span>
        </button>

        <div class="tq-collapse-body" id="tq-collapse-panel">
          <div class="tq-card" style="margin-bottom:24px;">
            <div class="tq-card-header" style="background:var(--tq-navy); color:#fff;">
              <i class="bi bi-plus-circle me-2"></i>New Question
            </div>
            <div class="tq-card-body">
              <form method="POST">
                <input type="hidden" name="_action" value="add_question">
                <div class="tq-form-group">
                  <label class="tq-label">Question Text</label>
                  <textarea name="question_text" class="tq-textarea" placeholder="Enter the question" required style="min-height:80px;"></textarea>
                </div>
                <div class="tq-form-row">
                  <div class="tq-form-group" style="margin-bottom:0;">
                    <label class="tq-label">Option A</label>
                    <input type="text" name="option_a" class="tq-input" placeholder="Option A" required>
                  </div>
                  <div class="tq-form-group" style="margin-bottom:0;">
                    <label class="tq-label">Option B</label>
                    <input type="text" name="option_b" class="tq-input" placeholder="Option B" required>
                  </div>
                </div>
                <div class="tq-form-row" style="margin-top:12px;">
                  <div class="tq-form-group" style="margin-bottom:0;">
                    <label class="tq-label">Option C</label>
                    <input type="text" name="option_c" class="tq-input" placeholder="Option C" required>
                  </div>
                  <div class="tq-form-group" style="margin-bottom:0;">
                    <label class="tq-label">Option D</label>
                    <input type="text" name="option_d" class="tq-input" placeholder="Option D" required>
                  </div>
                </div>
                <div class="tq-form-group" style="margin-top:12px;">
                  <label class="tq-label">Correct Answer</label>
                  <select name="correct_option" class="tq-select" required>
                    <option value="">— Select correct option —</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                  </select>
                </div>
                <div style="margin-top:16px;">
                  <button type="submit" name="add_question" class="btn-tq-gold">
                    <i class="bi bi-plus-circle"></i> Add Question
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Questions Table -->
        <div class="tq-section-header" style="font-size:15px;">
          <i class="bi bi-list-ol"></i>
          Existing Questions
          <span class="tq-badge tq-badge-navy" style="margin-left:8px;"><?= count($questions) ?></span>
        </div>

        <?php if (empty($questions)): ?>
        <div class="tq-card">
          <div class="tq-card-body">
            <div class="tq-empty">
              <span class="tq-empty-icon"><i class="bi bi-question-circle"></i></span>
              <div class="tq-empty-text">No questions yet</div>
              <div class="tq-empty-sub">Use the form above to add the first question.</div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="tq-card" style="overflow:hidden;">
          <div style="overflow-x:auto;">
            <table class="tq-table">
              <thead>
                <tr>
                  <th style="width:40px;">#</th>
                  <th>Question</th>
                  <th style="text-align:center; width:80px;">Correct</th>
                  <th style="text-align:center; width:120px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($questions as $idx => $q): ?>
                <tr>
                  <td style="color:var(--tq-muted); font-weight:600;"><?= $idx + 1 ?></td>
                  <td><?= htmlspecialchars($q['question_text']) ?></td>
                  <td style="text-align:center;">
                    <span class="tq-badge tq-badge-success" style="font-size:13px;"><?= htmlspecialchars($q['correct_option']) ?></span>
                  </td>
                  <td style="text-align:center;">
                    <a href="edit_question.php?id=<?= $q['id'] ?>&module_id=<?= $module_id ?>"
                       class="btn-tq-outline" style="padding:5px 12px; font-size:12px;">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="?module_id=<?= $module_id ?>&delete=<?= $q['id'] ?>"
                       class="btn-tq-danger" style="padding:5px 10px; font-size:12px;"
                       onclick="return confirm('Delete this question?')" title="Delete">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
</body>
</html>
