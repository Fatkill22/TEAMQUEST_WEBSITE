<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') { die("Access Denied"); }

$module_id = (int)$_GET['id'];

if (isset($_POST['save_master_key'])) {
    for ($i = 1; $i <= 50; $i++) {
        $val = isset($_POST["ans_$i"]) ? (int)$_POST["ans_$i"] : 0;
        $conn->query("INSERT INTO gauge_answers (module_id, question_num, correct_val)
                      VALUES ($module_id, $i, $val)
                      ON DUPLICATE KEY UPDATE correct_val = $val");
    }
    header("Location: admin_modules.php?tab=gauge-admin&saved=1");
    exit();
}

$master_key = [];
$res = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
while ($row = $res->fetch_assoc()) { $master_key[$row['question_num']] = $row['correct_val']; }

$mod_res    = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$mod_title  = ($mod_res->fetch_assoc())['title'] ?? 'Gauge Study';
$admin_user = $_SESSION['username'];
$avatar     = strtoupper(substr($admin_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Answer Key — <?= htmlspecialchars($mod_title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="tq-shell">

  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">Master Answer Key Editor</span>
    <div class="tq-user-badge">
      <div class="tq-avatar"><?= $avatar ?></div>
      <span>Admin: <?= htmlspecialchars($admin_user) ?></span>
    </div>
  </header>

  <div class="tq-body">
    <nav class="tq-sidebar">
      <ul class="tq-nav">
        <li class="tq-nav-item"><a href="admin_modules.php?tab=home" class="tq-nav-link"><i class="bi bi-house-fill"></i><span>Admin Home</span></a></li>
        <li class="tq-nav-item"><a href="admin_modules.php?tab=admin" class="tq-nav-link"><i class="bi bi-layers-fill"></i><span>Manage Modules</span></a></li>
        <li class="tq-nav-item active"><a href="admin_modules.php?tab=gauge-admin" class="tq-nav-link"><i class="bi bi-clipboard-data-fill"></i><span>Gauge Study</span></a></li>
        <li class="tq-nav-item"><a href="admin_modules.php?tab=users" class="tq-nav-link"><i class="bi bi-people-fill"></i><span>User Attempts</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>

    <main class="tq-content">
      <div style="max-width:860px; margin:0 auto;">

        <div class="tq-section-header"><i class="bi bi-key-fill"></i>Master Answer Key — <?= htmlspecialchars($mod_title) ?></div>

        <div class="tq-card" style="margin-bottom:16px; border-left:5px solid var(--tq-danger);">
          <div class="tq-card-body" style="padding:12px 20px; font-size:13px; color:var(--tq-danger); font-weight:600;">
            <i class="bi bi-shield-lock-fill me-2"></i>Admin Use Only — Changes affect all future scoring calculations
          </div>
        </div>

        <div class="tq-card" style="margin-bottom:8px; background:rgba(200,150,12,0.08); border:none; box-shadow:none;">
          <div class="tq-card-body" style="padding:10px 20px; font-size:13px; font-weight:600; color:var(--tq-gold); text-align:center;">
            <i class="bi bi-info-circle me-1"></i>
            Click <strong>1</strong> = Good Part &nbsp;·&nbsp; Click <strong>0</strong> = Reject/Bad Part
          </div>
        </div>

        <form method="POST">
          <div class="tq-card">
            <!-- 10-column × 5-row grid -->
            <div style="padding:20px;">
              <?php for ($row = 0; $row < 5; $row++): ?>
              <div style="display:grid; grid-template-columns:repeat(10, 1fr); gap:8px; margin-bottom:12px;">
                <?php for ($col = 1; $col <= 10; $col++):
                  $i = $row * 10 + $col;
                  $current = $master_key[$i] ?? 0;
                ?>
                <div style="text-align:center;">
                  <div style="font-size:11px; font-weight:700; color:var(--tq-muted); margin-bottom:4px;"><?= $i ?></div>
                  <input type="hidden" name="ans_<?= $i ?>" id="ans<?= $i ?>_val" value="<?= $current ?>">
                  <div class="tq-toggle-wrap" style="flex-direction:column; gap:3px;">
                    <button type="button" class="tq-toggle-btn <?= $current == 1 ? 'active-1' : '' ?>"
                            data-val="1" data-target="ans<?= $i ?>_val" style="padding:3px 8px; font-size:13px; min-width:36px;">1</button>
                    <button type="button" class="tq-toggle-btn <?= $current == 0 ? 'active-0' : '' ?>"
                            data-val="0" data-target="ans<?= $i ?>_val" style="padding:3px 8px; font-size:13px; min-width:36px;">0</button>
                  </div>
                </div>
                <?php endfor; ?>
              </div>
              <?php if ($row < 4): ?>
              <div class="tq-divider"></div>
              <?php endif; ?>
              <?php endfor; ?>
            </div>
          </div>

          <div style="display:flex; gap:12px; margin-top:16px;">
            <a href="admin_modules.php?tab=gauge-admin" class="btn-tq-outline">
              <i class="bi bi-arrow-left"></i> Cancel
            </a>
            <button type="submit" name="save_master_key" class="btn-tq-gold" style="flex:1; justify-content:center; height:46px; font-size:15px;">
              <i class="bi bi-floppy-fill"></i> Save Master Key
            </button>
          </div>
        </form>

      </div>
    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
</body>
</html>
