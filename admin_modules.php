<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.html");
    exit();
}

if (isset($_POST['add']) || (isset($_POST['_action']) && $_POST['_action'] === 'add')) {
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $content     = $_POST['content'];
    $depts_raw   = (array)($_POST['departments'] ?? ['all']);
    $department  = in_array('all', $depts_raw) ? 'all' : implode(',', array_filter($depts_raw));

    $imageName = !empty($_FILES['image']['name']) ? time() . "_" . $_FILES['image']['name'] : "";
    $videoName = !empty($_FILES['video']['name']) ? time() . "_" . $_FILES['video']['name'] : "";

    if ($imageName) { move_uploaded_file($_FILES['image']['tmp_name'], "Images/" . $imageName); }
    if ($videoName) { move_uploaded_file($_FILES['video']['tmp_name'], "Videos/" . $videoName); }

    $stmt = $conn->prepare("INSERT INTO modules (title, description, content, image, video, department) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $title, $description, $content, $imageName, $videoName, $department);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_modules.php?tab=admin&added=1");
    exit();
}

if (isset($_GET['reset_user']) && isset($_GET['mid'])) {
    $user_to_reset = $_GET['reset_user'];
    $mid           = (int)$_GET['mid'];
    $stmt = $conn->prepare("DELETE FROM exam_results WHERE username = ? AND module_id = ?");
    $stmt->bind_param("si", $user_to_reset, $mid);
    $stmt->execute();
    $stmt->close();
    $stmt2 = $conn->prepare("DELETE FROM gauge_attempt_details WHERE username = ? AND module_id = ?");
    $stmt2->bind_param("si", $user_to_reset, $mid);
    $stmt2->execute();
    $stmt2->close();
    header("Location: admin_modules.php?tab=users&reset=1");
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM questions             WHERE module_id = $id");
    $conn->query("DELETE FROM exam_results          WHERE module_id = $id");
    $conn->query("DELETE FROM gauge_answers         WHERE module_id = $id");
    $conn->query("DELETE FROM gauge_attempt_details WHERE module_id = $id");
    $conn->query("DELETE FROM modules               WHERE id = $id");
    header("Location: admin_modules.php?tab=admin&deleted=1");
    exit();
}

$modules = $conn->query("SELECT * FROM modules ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Display-only mapping for stored department codes → readable labels
function deptDisplay(string $raw): string {
    $map = ['STOR' => 'STORE', 'MARKETING' => 'MARKETING/SALES'];
    return implode(', ', array_map(
        fn($p) => $map[trim($p)] ?? trim($p),
        explode(',', $raw)
    ));
}

// Autocomplete data for search bars
$ac_modules = $ac_gauge = $ac_users = $ac_mod_all = [];
foreach ($modules as $_m) {
    if (stripos($_m['title'], 'Gauge') !== false) $ac_gauge[]   = htmlspecialchars($_m['title']);
    else                                           $ac_modules[] = htmlspecialchars($_m['title']);
    $ac_mod_all[] = htmlspecialchars($_m['title']);
}
$_ur = $conn->query("SELECT DISTINCT username FROM users ORDER BY username");
while ($_u = $_ur->fetch_assoc()) $ac_users[] = htmlspecialchars($_u['username']);

// ── Admin stat queries ────────────────────────────────────
$total_users_res  = $conn->query("SELECT COUNT(*) as cnt FROM users");
$total_users      = $total_users_res ? (int)$total_users_res->fetch_assoc()['cnt'] : 0;

$total_modules = count($modules);

$pass_res    = $conn->query("SELECT score, total_questions FROM exam_results WHERE total_questions > 0");
$total_exams = 0; $passed = 0;
if ($pass_res) {
    while ($er = $pass_res->fetch_assoc()) {
        $total_exams++;
        if ($er['total_questions'] > 0 && ($er['score'] / $er['total_questions']) >= 0.7) $passed++;
    }
}
$pass_rate = $total_exams > 0 ? round(($passed / $total_exams) * 100) : 0;

$gauge_cnt_res   = $conn->query("SELECT COUNT(*) as cnt FROM exam_results WHERE total_questions = 50");
$gauge_cnt       = $gauge_cnt_res ? (int)$gauge_cnt_res->fetch_assoc()['cnt'] : 0;

$admin_user = $_SESSION['username'];
$avatar     = strtoupper(substr($admin_user, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Panel — TeamQuest</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .tq-dept-wrap{position:relative;}
    .tq-dept-trigger{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border:1px solid var(--tq-border,#dde3ec);border-radius:6px;background:#fff;cursor:pointer;font-size:13px;min-height:38px;user-select:none;}
    .tq-dept-trigger:hover{border-color:var(--tq-navy,#1a2e4a);}
    .tq-dept-label{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--tq-text,#2d3748);}
    .tq-dept-panel{display:none;position:absolute;z-index:200;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid var(--tq-border,#dde3ec);border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.12);max-height:240px;overflow-y:auto;padding:6px 0;}
    .tq-dept-wrap.open .tq-dept-panel{display:block;}
    .tq-dept-item{display:flex;align-items:center;gap:8px;padding:7px 14px;font-size:13px;cursor:pointer;color:var(--tq-text,#2d3748);}
    .tq-dept-item:hover{background:var(--tq-light,#f4f7fb);}
    .tq-dept-item input[type=checkbox]{accent-color:var(--tq-navy,#1a2e4a);width:14px;height:14px;cursor:pointer;}
    .tq-dept-all{font-weight:600;border-bottom:1px solid var(--tq-border,#dde3ec);margin-bottom:4px;padding-bottom:10px;}
  </style>
</head>
<body>
<div class="tq-shell">

  <!-- TOPBAR -->
  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title" id="tq-page-title">Admin Home</span>
    <div class="tq-user-badge">
      <div class="tq-avatar"><?= $avatar ?></div>
      <span>Admin: <?= htmlspecialchars($admin_user) ?></span>
    </div>
  </header>

  <div class="tq-body">

    <!-- SIDEBAR -->
    <nav class="tq-sidebar">
      <ul class="tq-nav">
        <li class="tq-nav-item active">
          <a href="?tab=home" class="tq-nav-link" data-section="home">
            <i class="bi bi-house-fill"></i><span>Admin Home</span>
          </a>
        </li>
        <li class="tq-nav-item">
          <a href="?tab=admin" class="tq-nav-link" data-section="admin">
            <i class="bi bi-layers-fill"></i><span>Manage Modules</span>
          </a>
        </li>
        <li class="tq-nav-item">
          <a href="?tab=gauge-admin" class="tq-nav-link" data-section="gauge-admin">
            <i class="bi bi-clipboard-data-fill"></i><span>Gauge Study</span>
          </a>
        </li>
        <li class="tq-nav-item">
          <a href="?tab=users" class="tq-nav-link" data-section="users">
            <i class="bi bi-people-fill"></i><span>User Attempts</span>
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
    <main class="tq-content">

      <!-- ═══════════ HOME SECTION ═══════════ -->
      <section class="tq-section active" id="section-home">

        <div class="tq-stat-grid">
          <div class="tq-stat-card">
            <div class="tq-stat-icon"><i class="bi bi-people-fill"></i></div>
            <div>
              <div class="tq-stat-value"><?= $total_users ?></div>
              <div class="tq-stat-label">Total Users</div>
            </div>
          </div>
          <div class="tq-stat-card">
            <div class="tq-stat-icon"><i class="bi bi-layers-fill"></i></div>
            <div>
              <div class="tq-stat-value"><?= $total_modules ?></div>
              <div class="tq-stat-label">Active Modules</div>
            </div>
          </div>
          <div class="tq-stat-card">
            <div class="tq-stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
              <div class="tq-stat-value"><?= $pass_rate ?>%</div>
              <div class="tq-stat-label">Overall Pass Rate</div>
            </div>
          </div>
          <div class="tq-stat-card">
            <div class="tq-stat-icon"><i class="bi bi-clipboard-data-fill"></i></div>
            <div>
              <div class="tq-stat-value"><?= $gauge_cnt ?></div>
              <div class="tq-stat-label">Gauge Exams Taken</div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="tq-section-header" style="margin-top:8px;"><i class="bi bi-lightning-fill"></i>Quick Actions</div>
        <div class="tq-quick-actions">
          <a href="#" data-goto-section="admin" class="tq-quick-action">
            <i class="bi bi-plus-circle-fill"></i>
            <div>
              <div style="font-size:15px;">Add Module</div>
              <div style="font-size:12px; opacity:.8; font-weight:400;">Create a new training module</div>
            </div>
          </a>
          <a href="#" data-goto-section="gauge-admin" class="tq-quick-action">
            <i class="bi bi-clipboard-data-fill"></i>
            <div>
              <div style="font-size:15px;">Gauge Study</div>
              <div style="font-size:12px; opacity:.8; font-weight:400;">Manage gauge exams &amp; keys</div>
            </div>
          </a>
          <a href="#" data-goto-section="users" class="tq-quick-action">
            <i class="bi bi-person-lines-fill"></i>
            <div>
              <div style="font-size:15px;">User Attempts</div>
              <div style="font-size:12px; opacity:.8; font-weight:400;">View &amp; reset employee progress</div>
            </div>
          </a>
        </div>

        <!-- Module Summary -->
        <div class="tq-card">
          <div class="tq-card-header"><i class="bi bi-table me-2" style="color:var(--tq-gold);"></i>All Modules at a Glance</div>
          <div style="overflow-x:auto;">
            <table class="tq-table">
              <thead><tr><th>Title</th><th>Department</th><th>Type</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach ($modules as $m): $is_g = stripos($m['title'], 'Gauge') !== false; ?>
                <tr>
                  <td style="font-weight:500;"><?= htmlspecialchars($m['title']) ?></td>
                  <td><?= deptDisplay($m['department']) ?></td>
                  <td><span class="tq-badge <?= $is_g ? 'tq-badge-warning' : 'tq-badge-navy' ?>"><?= $is_g ? 'Gauge' : 'Module' ?></span></td>
                  <td>
                    <?php if (!$is_g): ?>
                    <a href="edit_module.php?id=<?= $m['id'] ?>" class="btn-tq-outline" style="padding:5px 10px; font-size:12px;">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="manage_exam.php?module_id=<?= $m['id'] ?>" class="btn-tq-primary" style="padding:5px 12px; font-size:12px;">
                      <i class="bi bi-question-circle"></i> Quiz
                    </a>
                    <?php else: ?>
                    <a href="view_gauge_report.php?id=<?= $m['id'] ?>" class="btn-tq-gold" style="padding:5px 12px; font-size:12px;">
                      <i class="bi bi-bar-chart"></i> Report
                    </a>
                    <a href="gauge_control.php?id=<?= $m['id'] ?>" class="btn-tq-outline" style="padding:5px 12px; font-size:12px;">
                      <i class="bi bi-key"></i> Key
                    </a>
                    <?php endif; ?>
                    <a href="?delete=<?= $m['id'] ?>&tab=home" class="btn-tq-danger" style="padding:5px 10px; font-size:12px;"
                       onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($m['title'])) ?>\'? This cannot be undone.')">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($modules)): ?>
                <tr><td colspan="4">
                  <div class="tq-empty" style="padding:28px;">
                    <span class="tq-empty-icon"><i class="bi bi-inbox"></i></span>
                    <div class="tq-empty-text">No modules yet — add one below</div>
                  </div>
                </td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ═══════════ MANAGE MODULES SECTION ═══════════ -->
      <section class="tq-section" id="section-admin">
        <div class="tq-section-header"><i class="bi bi-layers-fill"></i>Manage Modules</div>

        <!-- Collapsible Add Form -->
        <button class="tq-collapse-toggle" id="tq-collapse-btn" type="button">
          <i class="bi bi-chevron-down"></i>
          <span class="btn-label">Add New Module / Gauge Study</span>
        </button>

        <div class="tq-collapse-body" id="tq-collapse-panel">
          <div class="tq-card" style="margin-bottom:24px;">
            <div class="tq-card-header" style="background:var(--tq-navy); color:#fff;">
              <i class="bi bi-plus-circle me-2"></i>New Module / Gauge Study
            </div>
            <div class="tq-card-body">
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_action" value="add">
                <div class="tq-form-row">
                  <div class="tq-form-group" style="margin-bottom:0;">
                    <label class="tq-label">Title <small style="color:var(--tq-muted); font-weight:400;">(include "Gauge" for gauge studies)</small></label>
                    <input type="text" name="title" class="tq-input" placeholder="Module title" required>
                  </div>
                  <div class="tq-form-group" style="margin-bottom:0;">
                    <label class="tq-label">Department</label>
                    <div class="tq-dept-wrap" id="deptWrap_new">
                      <div class="tq-dept-trigger" onclick="tqDeptToggle('deptWrap_new')">
                        <span class="tq-dept-label" id="deptLabel_new">All Departments</span>
                        <i class="bi bi-chevron-down" style="font-size:11px;"></i>
                      </div>
                      <div class="tq-dept-panel" id="deptPanel_new">
                        <?php
                        $dept_list = [
                          'all'         => 'All Departments',
                          'ACCOUNTING'  => 'ACCOUNTING',
                          'ASEPH BURN-IN'=> 'ASEPH BURN-IN',
                          'CML BURN-IN' => 'CML BURN-IN',
                          'ENGINEERING' => 'ENGINEERING',
                          'HR/ADMIN'    => 'HR/ADMIN',
                          'LOGISTICS'   => 'LOGISTICS',
                          'MACHINING'   => 'MACHINING',
                          'MARKETING'   => 'MARKETING/SALES',
                          'MIS'         => 'MIS',
                          'PLANNING'    => 'PLANNING',
                          'PRODUCTION'  => 'PRODUCTION',
                          'PURCHASING'  => 'PURCHASING',
                          'QA'          => 'QA',
                          'QA/TRAINING' => 'QA/TRAINING',
                          'STOR'        => 'STORE',
                          'WAREHOUSE'   => 'WAREHOUSE',
                        ];
                        foreach ($dept_list as $val => $label): ?>
                        <label class="tq-dept-item<?= $val === 'all' ? ' tq-dept-all' : '' ?>">
                          <input type="checkbox" name="departments[]" value="<?= $val ?>"
                                 <?= $val === 'all' ? 'checked' : '' ?>
                                 onchange="tqDeptChange('deptWrap_new', this)">
                          <?= $label ?>
                        </label>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="tq-form-group">
                  <label class="tq-label">Description</label>
                  <textarea name="description" class="tq-textarea" placeholder="Brief description of this module"></textarea>
                </div>
                <div class="tq-form-group">
                  <label class="tq-label">Content / Module Information</label>
                  <textarea name="content" class="tq-textarea" style="min-height:100px;" placeholder="Detailed module content"></textarea>
                </div>
                <div class="tq-form-row">
                  <div class="tq-form-group" style="margin-bottom:0;">
                    <label class="tq-label"><i class="bi bi-image me-1"></i>Cover Image</label>
                    <input type="file" name="image" accept="image/*" class="tq-input" style="padding:8px 14px; height:auto;">
                  </div>
                  <div class="tq-form-group" style="margin-bottom:0;">
                    <label class="tq-label"><i class="bi bi-camera-video me-1"></i>Training Video</label>
                    <input type="file" name="video" accept="video/*" class="tq-input" style="padding:8px 14px; height:auto;">
                  </div>
                </div>
                <div style="margin-top:20px;">
                  <button type="submit" name="add" class="btn-tq-gold">
                    <i class="bi bi-cloud-upload"></i> Publish Module
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Normal Modules Table -->
        <div class="tq-section-header" style="font-size:15px;"><i class="bi bi-book-fill"></i>Normal Modules</div>
        <div style="margin-bottom:12px; max-width:360px;">
          <div style="position:relative;">
            <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--tq-muted);"></i>
            <input type="text" id="moduleSearchInput" class="tq-input" list="moduleTitleList"
                   placeholder="Search modules…" style="padding-left:40px;" autocomplete="off">
          </div>
          <datalist id="moduleTitleList">
            <?php foreach ($ac_modules as $t): ?><option value="<?= $t ?>"><?php endforeach; ?>
          </datalist>
        </div>
        <div class="tq-card">
          <div style="overflow-x:auto;">
            <table class="tq-table">
              <thead><tr><th>Title</th><th>Department</th><th style="text-align:center;">Actions</th></tr></thead>
              <tbody id="moduleTableBody">
                <?php foreach ($modules as $m): if (stripos($m['title'], 'Gauge') !== false) continue; ?>
                <tr>
                  <td style="font-weight:500;"><?= htmlspecialchars($m['title']) ?></td>
                  <td><?= deptDisplay($m['department']) ?></td>
                  <td style="text-align:center;">
                    <a href="edit_module.php?id=<?= $m['id'] ?>" class="btn-tq-outline" style="padding:6px 14px; font-size:12px;">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="manage_exam.php?module_id=<?= $m['id'] ?>" class="btn-tq-primary" style="padding:6px 14px; font-size:12px;">
                      <i class="bi bi-question-circle"></i> Manage Quiz
                    </a>
                    <a href="?delete=<?= $m['id'] ?>&tab=admin" class="btn-tq-danger" style="padding:6px 10px; font-size:12px;"
                       onclick="return confirm('Delete this module?')" title="Delete module">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ═══════════ GAUGE ADMIN SECTION ═══════════ -->
      <section class="tq-section" id="section-gauge-admin">
        <div class="tq-section-header"><i class="bi bi-clipboard-data-fill"></i>Gauge Study Management</div>
        <div style="margin-bottom:12px; max-width:360px;">
          <div style="position:relative;">
            <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--tq-muted);"></i>
            <input type="text" id="gaugeSearchInput" class="tq-input" list="gaugeTitleList"
                   placeholder="Search gauge studies…" style="padding-left:40px;" autocomplete="off">
          </div>
          <datalist id="gaugeTitleList">
            <?php foreach ($ac_gauge as $t): ?><option value="<?= $t ?>"><?php endforeach; ?>
          </datalist>
        </div>
        <div class="tq-card">
          <div style="overflow-x:auto;">
            <table class="tq-table">
              <thead><tr><th>Study Title</th><th>Department</th><th style="text-align:center;">Actions</th></tr></thead>
              <tbody id="gaugeTableBody">
                <?php
                $has_gauge = false;
                foreach ($modules as $m): if (stripos($m['title'], 'Gauge') === false) continue;
                $has_gauge = true;
                ?>
                <tr>
                  <td style="font-weight:600; color:var(--tq-navy);"><?= htmlspecialchars($m['title']) ?></td>
                  <td><?= deptDisplay($m['department']) ?></td>
                  <td style="text-align:center;">
                    <a href="view_gauge_report.php?id=<?= $m['id'] ?>" class="btn-tq-gold" style="padding:6px 14px; font-size:12px;">
                      <i class="bi bi-bar-chart-fill"></i> View Report
                    </a>
                    <a href="gauge_control.php?id=<?= $m['id'] ?>" class="btn-tq-outline" style="padding:6px 14px; font-size:12px;">
                      <i class="bi bi-key-fill"></i> Answer Key
                    </a>
                    <a href="?delete=<?= $m['id'] ?>&tab=gauge-admin" class="btn-tq-danger" style="padding:6px 10px; font-size:12px;"
                       onclick="return confirm('Delete this gauge study?')" title="Delete">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$has_gauge): ?>
                <tr><td colspan="3">
                  <div class="tq-empty" style="padding:28px;">
                    <span class="tq-empty-icon"><i class="bi bi-clipboard-x"></i></span>
                    <div class="tq-empty-text">No gauge studies yet</div>
                  </div>
                </td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ═══════════ USER ATTEMPTS SECTION ═══════════ -->
      <section class="tq-section" id="section-users">
        <div class="tq-section-header"><i class="bi bi-people-fill"></i>Employee Progress</div>
        <div style="margin-bottom:16px; max-width:360px;">
          <div style="position:relative;">
            <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--tq-muted);"></i>
            <input type="text" id="userSearchInput" class="tq-input" list="userSearchList"
                   placeholder="Search by name or module…" style="padding-left:40px;" autocomplete="off">
          </div>
        </div>
        <datalist id="userSearchList">
          <?php foreach ($ac_users as $u): ?><option value="<?= $u ?>"><?php endforeach; ?>
          <?php foreach ($ac_mod_all as $t): ?><option value="<?= $t ?>"><?php endforeach; ?>
        </datalist>
        <div class="tq-card" style="overflow:hidden;">
          <div style="overflow-x:auto;">
            <table class="tq-table">
              <thead><tr><th>Username</th><th>Module</th><th>Score</th><th>Attempts</th><th>Actions</th></tr></thead>
              <tbody id="attemptsTableBody">
                <?php
                $all_res = $conn->query("SELECT r.*, m.title FROM exam_results r JOIN modules m ON r.module_id = m.id ORDER BY r.username ASC");
                while ($row = $all_res->fetch_assoc()):
                  $apct = $row['total_questions'] > 0 ? round(($row['score']/$row['total_questions'])*100) : 0;
                ?>
                <tr>
                  <td style="font-weight:600;"><?= htmlspecialchars($row['username']) ?></td>
                  <td><?= htmlspecialchars($row['title']) ?></td>
                  <td>
                    <?php if ($row['total_questions'] == 0): ?>
                    <span class="tq-badge tq-badge-success">Completed</span>
                    <?php else: ?>
                    <?= $row['score'] ?> / <?= $row['total_questions'] ?>
                    <span class="tq-badge <?= $apct >= 70 ? 'tq-badge-success' : 'tq-badge-danger' ?>" style="margin-left:6px;"><?= $apct ?>%</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="tq-badge <?= $row['attempts'] >= 3 ? 'tq-badge-danger' : 'tq-badge-navy' ?>">
                      <?= $row['attempts'] ?> / 3
                    </span>
                  </td>
                  <td>
                    <a href="?reset_user=<?= urlencode($row['username']) ?>&mid=<?= $row['module_id'] ?>"
                       class="btn-tq-danger" style="padding:5px 10px; font-size:12px;"
                       onclick="return confirm('Reset progress for <?= htmlspecialchars(addslashes($row['username'])) ?>?')"
                       title="Reset this user's attempts">
                      <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </main>
  </div>
</div>

<script src="assets/js/scripts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function tqTableSearch(inputId, tbodyId) {
        const inp = document.getElementById(inputId);
        if (!inp) return;
        inp.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#' + tbodyId + ' tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
    tqTableSearch('moduleSearchInput', 'moduleTableBody');
    tqTableSearch('gaugeSearchInput',  'gaugeTableBody');
});

function tqDeptToggle(wrapId) {
    const wrap = document.getElementById(wrapId);
    const isOpen = wrap.classList.toggle('open');
    if (isOpen) {
        document.addEventListener('click', function handler(e) {
            if (!wrap.contains(e.target)) { wrap.classList.remove('open'); document.removeEventListener('click', handler); }
        });
    }
}
function tqDeptChange(wrapId, cb) {
    const wrap  = document.getElementById(wrapId);
    const all   = wrap.querySelector('input[value="all"]');
    const others = Array.from(wrap.querySelectorAll('input[type=checkbox]')).filter(i => i.value !== 'all');
    if (cb.value === 'all') {
        others.forEach(i => { i.checked = false; i.disabled = cb.checked; });
    } else {
        if (cb.checked) { all.checked = false; all.disabled = false; }
        if (!others.some(i => i.checked)) { all.checked = true; }
    }
    const checked = Array.from(wrap.querySelectorAll('input:checked'));
    const label   = document.getElementById('deptLabel_' + wrapId.split('_')[1]);
    if (!checked.length || (checked.length === 1 && checked[0].value === 'all')) {
        label.textContent = 'All Departments';
    } else {
        label.textContent = checked.map(i => i.parentElement.textContent.trim()).join(', ');
    }
}
</script>
</body>
</html>
