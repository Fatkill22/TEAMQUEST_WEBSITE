<?php
ob_start();
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.html"); ob_end_flush(); exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_modules.php?tab=admin"); ob_end_flush(); exit();
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$module = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$module) {
    header("Location: admin_modules.php?tab=admin"); ob_end_flush(); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $content     = trim($_POST['content']     ?? '');
    $depts_raw   = (array)($_POST['departments'] ?? ['all']);
    $department  = in_array('all', $depts_raw) ? 'all' : implode(',', array_filter($depts_raw));

    $imageName = $module['image'];
    $videoName = $module['video'];

    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "Images/" . $imageName);
    }
    if (!empty($_FILES['video']['name'])) {
        $videoName = time() . "_" . basename($_FILES['video']['name']);
        move_uploaded_file($_FILES['video']['tmp_name'], "Videos/" . $videoName);
    }

    $upd = $conn->prepare("UPDATE modules SET title=?, description=?, content=?, image=?, video=?, department=? WHERE id=?");
    $upd->bind_param("ssssssi", $title, $description, $content, $imageName, $videoName, $department, $id);
    $upd->execute();
    $upd->close();

    $back_tab = stripos($title, 'Gauge') !== false ? 'gauge-admin' : 'admin';
    header("Location: admin_modules.php?tab={$back_tab}&saved=1");
    ob_end_flush();
    exit();
}

$admin_user = $_SESSION['username'];
$avatar     = strtoupper(substr($admin_user, 0, 1));
$depts = [
    'all'          => 'All Departments',
    'ACCOUNTING'   => 'ACCOUNTING',
    'ASEPH BURN-IN'=> 'ASEPH BURN-IN',
    'CML BURN-IN'  => 'CML BURN-IN',
    'ENGINEERING'  => 'ENGINEERING',
    'HR/ADMIN'     => 'HR/ADMIN',
    'LOGISTICS'    => 'LOGISTICS',
    'MACHINING'    => 'MACHINING',
    'MARKETING'    => 'MARKETING/SALES',
    'MIS'          => 'MIS',
    'PLANNING'     => 'PLANNING',
    'PRODUCTION'   => 'PRODUCTION',
    'PURCHASING'   => 'PURCHASING',
    'QA'           => 'QA',
    'QA/TRAINING'  => 'QA/TRAINING',
    'STOR'         => 'STORE',
    'WAREHOUSE'    => 'WAREHOUSE',
];
$saved_depts = array_filter(explode(',', $module['department'] ?? 'all'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Module — TeamQuest</title>
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

  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">Edit Module</span>
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
      <div style="max-width:760px; margin:0 auto;">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
          <div class="tq-section-header" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
            <i class="bi bi-pencil-fill"></i>Edit Module
          </div>
          <a href="admin_modules.php?tab=admin" class="btn-tq-outline">
            <i class="bi bi-arrow-left"></i> Back
          </a>
        </div>

        <div class="tq-card">
          <div class="tq-card-header" style="background:var(--tq-navy); color:#fff;">
            <i class="bi bi-pencil-square me-2"></i><?= htmlspecialchars($module['title']) ?>
          </div>
          <div class="tq-card-body">
            <form method="POST" enctype="multipart/form-data">

              <div class="tq-form-row">
                <div class="tq-form-group" style="margin-bottom:0;">
                  <label class="tq-label">Title</label>
                  <input type="text" name="title" class="tq-input"
                         value="<?= htmlspecialchars($module['title']) ?>" required>
                </div>
                <div class="tq-form-group" style="margin-bottom:0;">
                  <label class="tq-label">Department</label>
                  <div class="tq-dept-wrap" id="deptWrap_edit">
                    <div class="tq-dept-trigger" onclick="tqDeptToggle('deptWrap_edit')">
                      <span class="tq-dept-label" id="deptLabel_edit">
                        <?php
                          if (in_array('all', $saved_depts) || empty($saved_depts)) echo 'All Departments';
                          else echo implode(', ', array_map(fn($v) => $depts[$v] ?? $v, $saved_depts));
                        ?>
                      </span>
                      <i class="bi bi-chevron-down" style="font-size:11px;"></i>
                    </div>
                    <div class="tq-dept-panel" id="deptPanel_edit">
                      <?php foreach ($depts as $val => $label): ?>
                      <label class="tq-dept-item<?= $val === 'all' ? ' tq-dept-all' : '' ?>">
                        <input type="checkbox" name="departments[]" value="<?= $val ?>"
                               <?= in_array($val, $saved_depts) || (empty($saved_depts) && $val === 'all') ? 'checked' : '' ?>
                               onchange="tqDeptChange('deptWrap_edit', this)">
                        <?= $label ?>
                      </label>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tq-form-group">
                <label class="tq-label">Description</label>
                <textarea name="description" class="tq-textarea"><?= htmlspecialchars($module['description']) ?></textarea>
              </div>

              <div class="tq-form-group">
                <label class="tq-label">Content / Module Information</label>
                <textarea name="content" class="tq-textarea" style="min-height:120px;"><?= htmlspecialchars($module['content'] ?? '') ?></textarea>
              </div>

              <div class="tq-form-row">
                <div class="tq-form-group" style="margin-bottom:0;">
                  <label class="tq-label"><i class="bi bi-image me-1"></i>Cover Image</label>
                  <input type="file" name="image" accept="image/*" class="tq-input" style="padding:8px 14px; height:auto;">
                  <?php if ($module['image']): ?>
                  <div style="margin-top:6px; font-size:12px; color:var(--tq-muted);">
                    Current: <?= htmlspecialchars($module['image']) ?>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="tq-form-group" style="margin-bottom:0;">
                  <label class="tq-label"><i class="bi bi-camera-video me-1"></i>Training Video</label>
                  <input type="file" name="video" accept="video/*" class="tq-input" style="padding:8px 14px; height:auto;">
                  <?php if ($module['video']): ?>
                  <div style="margin-top:6px; font-size:12px; color:var(--tq-muted);">
                    Current: <?= htmlspecialchars($module['video']) ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>

              <div style="display:flex; gap:12px; margin-top:24px;">
                <a href="admin_modules.php?tab=admin" class="btn-tq-outline">
                  <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="btn-tq-gold" style="flex:1; justify-content:center; height:46px; font-size:15px;">
                  <i class="bi bi-floppy-fill"></i> Save Changes
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
<script>
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
    const wrap   = document.getElementById(wrapId);
    const all    = wrap.querySelector('input[value="all"]');
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
