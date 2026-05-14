<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.html");
    exit();
}

$dept = $_SESSION['department'];
$user = $_SESSION['username'];

$modules = [];
$stmt = $conn->prepare("SELECT * FROM modules WHERE FIND_IN_SET(?, department) OR department = 'all' ORDER BY id DESC");
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $modules[] = $row; }
$stmt->close();

// ── Dashboard stat queries ────────────────────────────────
$non_gauge_modules = array_values(array_filter($modules, function($m) {
    return stripos($m['title'], 'Gauge') === false;
}));
$total_normal = count($non_gauge_modules);

$completed     = 0;
$total_score   = 0;
$score_count   = 0;
$completed_ids = [];

foreach ($non_gauge_modules as $m) {
    $mid = (int)$m['id'];
    $r   = $conn->query("SELECT score, total_questions FROM exam_results WHERE username = '$user' AND module_id = $mid");
    if ($r && $prow = $r->fetch_assoc()) {
        if ($prow['total_questions'] == 0) {
            // No-exam module completion
            $completed++;
            $completed_ids[] = $mid;
        } elseif (($prow['score'] / $prow['total_questions']) >= 0.7) {
            // Passed exam
            $completed++;
            $completed_ids[] = $mid;
            $total_score += ($prow['score'] / $prow['total_questions']) * 100;
            $score_count++;
        } else {
            $total_score += ($prow['score'] / $prow['total_questions']) * 100;
            $score_count++;
        }
    }
}
$avg_score = $score_count > 0 ? round($total_score / $score_count) : 0;

$gauge_taken = 0;
foreach ($modules as $m) {
    if (stripos($m['title'], 'Gauge') === false) continue;
    $mid = (int)$m['id'];
    $r   = $conn->query("SELECT id FROM exam_results WHERE username = '$user' AND module_id = $mid");
    if ($r && $r->num_rows > 0) $gauge_taken++;
}

// "Continue where you left off" — first non-completed module
$continue_module = null;
foreach ($non_gauge_modules as $m) {
    if (!in_array((int)$m['id'], $completed_ids)) { $continue_module = $m; break; }
}
$avatar = strtoupper(substr($user, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Online Training — TeamQuest</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .tq-mtab{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:24px;border:2px solid var(--tq-navy,#1a2e4a);background:#fff;color:var(--tq-navy,#1a2e4a);font-weight:600;font-size:13px;cursor:pointer;transition:background .18s,color .18s;}
    .tq-mtab.active{background:var(--tq-navy,#1a2e4a);color:#fff;}
    .tq-mtab-count{background:var(--tq-navy,#1a2e4a);color:#fff;border-radius:10px;padding:1px 8px;font-size:11px;font-weight:700;min-width:20px;text-align:center;}
    .tq-mtab.active .tq-mtab-count{background:rgba(255,255,255,0.25);}
  </style>
</head>
<body>
<div class="tq-shell">

  <!-- TOPBAR -->
  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title" id="tq-page-title">Home</span>
    <div class="tq-user-badge">
      <div class="tq-avatar"><?= $avatar ?></div>
      <span><?= htmlspecialchars($user) ?></span>
    </div>
  </header>

  <div class="tq-body">

    <!-- SIDEBAR -->
    <nav class="tq-sidebar">
      <ul class="tq-nav">
        <li class="tq-nav-item active" id="nav-home">
          <a href="?tab=home" class="tq-nav-link" data-section="home">
            <i class="bi bi-house-fill"></i><span>Home</span>
          </a>
        </li>
        <li class="tq-nav-item" id="nav-modules">
          <a href="?tab=modules" class="tq-nav-link" data-section="modules">
            <i class="bi bi-book-fill"></i><span>My Modules</span>
          </a>
        </li>
        <li class="tq-nav-item" id="nav-gauge">
          <a href="?tab=gauge" class="tq-nav-link" data-section="gauge">
            <i class="bi bi-clipboard-check-fill"></i><span>Gauge Exams</span>
          </a>
        </li>
        <li class="tq-nav-item" id="nav-results">
          <a href="?tab=results" class="tq-nav-link" data-section="results">
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
    <main class="tq-content">

      <!-- ═══════════ HOME SECTION ═══════════ -->
      <section class="tq-section active" id="section-home">

        <!-- Stat Cards -->
        <div class="tq-stat-grid">
          <div class="tq-stat-card">
            <div class="tq-stat-icon"><i class="bi bi-book-fill"></i></div>
            <div>
              <div class="tq-stat-value"><?= $total_normal ?></div>
              <div class="tq-stat-label">Modules Assigned</div>
            </div>
          </div>
          <div class="tq-stat-card">
            <div class="tq-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div>
              <div class="tq-stat-value"><?= $completed ?> / <?= $total_normal ?></div>
              <div class="tq-stat-label">Completed</div>
            </div>
          </div>
          <div class="tq-stat-card">
            <div class="tq-stat-icon"><i class="bi bi-star-fill"></i></div>
            <div>
              <div class="tq-stat-value"><?= $avg_score ?>%</div>
              <div class="tq-stat-label">Average Score</div>
            </div>
          </div>
          <div class="tq-stat-card">
            <div class="tq-stat-icon"><i class="bi bi-clipboard-data-fill"></i></div>
            <div>
              <div class="tq-stat-value"><?= $gauge_taken ?></div>
              <div class="tq-stat-label">Gauge Exams Taken</div>
            </div>
          </div>
        </div>

        <!-- Continue where you left off -->
        <?php if ($continue_module): ?>
        <div class="tq-continue-card">
          <img src="Images/<?= htmlspecialchars($continue_module['image'] ?: 'Module1.jpg') ?>" alt="">
          <div style="flex:1;">
            <div class="tq-continue-title"><?= htmlspecialchars($continue_module['title']) ?></div>
            <div class="tq-continue-sub">Continue your training — pick up where you left off</div>
          </div>
          <a href="view_module.php?id=<?= (int)$continue_module['id'] ?>" class="btn-tq-gold">
            <i class="bi bi-play-fill"></i> Continue
          </a>
        </div>
        <?php else: ?>
        <div class="tq-continue-card" style="border-left-color: var(--tq-success);">
          <div class="tq-stat-icon" style="background:#dcfce7; color:var(--tq-success); font-size:26px; width:56px; height:56px; flex-shrink:0;">
            <i class="bi bi-trophy-fill"></i>
          </div>
          <div>
            <div class="tq-continue-title">All modules completed — great work!</div>
            <div class="tq-continue-sub">Head to My Results to review your scores.</div>
          </div>
        </div>
        <?php endif; ?>

        <!-- My Progress -->
        <div class="tq-card">
          <div class="tq-card-header"><i class="bi bi-graph-up me-2" style="color:var(--tq-gold);"></i>My Progress</div>
          <div class="tq-card-body">
            <?php if (empty($non_gauge_modules)): ?>
            <div class="tq-empty">
              <span class="tq-empty-icon"><i class="bi bi-journal-x"></i></span>
              <div class="tq-empty-text">No modules assigned yet</div>
            </div>
            <?php else: ?>
            <?php foreach ($non_gauge_modules as $m):
              $mid  = (int)$m['id'];
              $pr   = $conn->query("SELECT score, total_questions FROM exam_results WHERE username = '$user' AND module_id = $mid");
              $prow = ($pr && $pr->num_rows > 0) ? $pr->fetch_assoc() : null;
              $pct  = 0; $pstatus = 'Not Started'; $pclass = 'empty'; $pcolor = 'var(--tq-muted)';
              if ($prow) {
                  if ($prow['total_questions'] == 0) {
                      $pct = 100; $pstatus = 'Completed'; $pclass = 'passed'; $pcolor = 'var(--tq-success)';
                  } elseif ($prow['total_questions'] > 0) {
                      $pct = round(($prow['score'] / $prow['total_questions']) * 100);
                      if ($pct >= 70) { $pstatus = 'Passed'; $pclass = 'passed'; $pcolor = 'var(--tq-success)'; }
                      else            { $pstatus = 'Failed'; $pclass = 'failed'; $pcolor = 'var(--tq-danger)'; }
                  }
              }
            ?>
            <div class="tq-progress-row">
              <div class="tq-progress-label" title="<?= htmlspecialchars($m['title']) ?>"><?= htmlspecialchars($m['title']) ?></div>
              <div class="tq-progress-bar-wrap">
                <div class="tq-progress-fill <?= $pclass ?>" style="width:<?= $pct ?>%"></div>
              </div>
              <div class="tq-progress-status" style="color:<?= $pcolor ?>"><?= $pstatus ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Recent Results -->
        <div class="tq-card">
          <div class="tq-card-header" style="justify-content: space-between;">
            <span><i class="bi bi-clock-history me-2" style="color:var(--tq-gold);"></i>Recent Results</span>
            <a href="#" data-goto-section="results" style="font-size:13px; color:var(--tq-gold); text-decoration:none; font-weight:600;">See All &rarr;</a>
          </div>
          <div style="overflow-x:auto;">
            <table class="tq-table">
              <thead><tr><th>Module</th><th>Score</th><th>Status</th><th>Type</th></tr></thead>
              <tbody>
                <?php
                $recent   = $conn->query("SELECT r.*, m.title FROM exam_results r JOIN modules m ON r.module_id = m.id WHERE r.username = '$user' AND r.total_questions > 0 ORDER BY r.id DESC LIMIT 3");
                $has_rec  = false;
                while ($rr = $recent->fetch_assoc()):
                  $has_rec = true;
                  $rpct = $rr['total_questions'] > 0 ? ($rr['score'] / $rr['total_questions']) * 100 : 0;
                ?>
                <tr class="<?= $rpct >= 70 ? 'row-pass' : 'row-fail' ?>">
                  <td><?= htmlspecialchars($rr['title']) ?></td>
                  <td><?= $rr['score'] ?> / <?= $rr['total_questions'] ?></td>
                  <td><span class="tq-badge <?= $rpct >= 70 ? 'tq-badge-success' : 'tq-badge-danger' ?>"><?= $rpct >= 70 ? 'Passed' : 'Failed' ?></span></td>
                  <td><span class="tq-badge tq-badge-navy"><?= $rr['total_questions'] == 50 ? 'Gauge' : 'Quiz' ?></span></td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$has_rec): ?>
                <tr><td colspan="4">
                  <div class="tq-empty" style="padding:24px 20px;">
                    <span class="tq-empty-icon"><i class="bi bi-inbox"></i></span>
                    <div class="tq-empty-text">No results yet — take an exam to see results here</div>
                  </div>
                </td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </section>

      <!-- ═══════════ MODULES SECTION ═══════════ -->
      <section class="tq-section" id="section-modules">
        <div class="tq-section-header"><i class="bi bi-book-fill"></i>Training Modules — <?= htmlspecialchars($dept) ?></div>

        <?php if (empty($non_gauge_modules)): ?>
        <div class="tq-empty">
          <span class="tq-empty-icon"><i class="bi bi-journal-x"></i></span>
          <div class="tq-empty-text">No modules assigned yet</div>
          <div class="tq-empty-sub">Check back soon!</div>
        </div>
        <?php else:
          $enrolled_modules = array_values(array_filter($non_gauge_modules, fn($m) => !in_array((int)$m['id'], $completed_ids)));
          $done_modules     = array_values(array_filter($non_gauge_modules, fn($m) =>  in_array((int)$m['id'], $completed_ids)));
        ?>

        <!-- Tab bar -->
        <div style="display:flex;gap:10px;margin-bottom:22px;flex-wrap:wrap;">
          <button class="tq-mtab active" onclick="switchModuleTab(this,'tab-enrolled')">
            <i class="bi bi-book"></i> Enrolled
            <span class="tq-mtab-count"><?= count($enrolled_modules) ?></span>
          </button>
          <button class="tq-mtab" onclick="switchModuleTab(this,'tab-completed')">
            <i class="bi bi-check-circle-fill"></i> Completed
            <span class="tq-mtab-count"><?= count($done_modules) ?></span>
          </button>
        </div>

        <!-- ── Enrolled tab ── -->
        <div id="tab-enrolled">
          <?php if (empty($enrolled_modules)): ?>
          <div class="tq-empty">
            <span class="tq-empty-icon"><i class="bi bi-trophy-fill"></i></span>
            <div class="tq-empty-text">All modules completed — great work!</div>
          </div>
          <?php else: ?>
          <div class="tq-module-grid">
            <?php foreach ($enrolled_modules as $module):
              $mod_id  = (int)$module['id'];
              $chk_att = $conn->query("SELECT attempts, score, total_questions FROM exam_results WHERE username = '$user' AND module_id = $mod_id");
              $att_row = ($chk_att && $chk_att->num_rows > 0) ? $chk_att->fetch_assoc() : null;
              $attempts = $att_row ? (int)$att_row['attempts'] : 0;

              $status = 'Not Started'; $status_class = 'tq-badge-gray';
              if ($att_row && $att_row['total_questions'] > 0) {
                  if ($attempts >= 3) { $status = 'Locked';  $status_class = 'tq-badge-danger'; }
                  else                { $status = 'Retake';  $status_class = 'tq-badge-warning'; }
              }
              $step = $att_row ? 3 : 1;
            ?>
            <div class="tq-module-card" onclick="location.href='view_module.php?id=<?= $module['id'] ?>'">
              <div style="position:relative;overflow:hidden;">
                <img src="Images/<?= htmlspecialchars($module['image'] ?: 'Module1.jpg') ?>" class="tq-module-img">
              </div>
              <div class="tq-module-body">
                <div class="tq-module-title"><?= htmlspecialchars($module['title']) ?></div>
                <div class="tq-module-desc"><?= htmlspecialchars($module['description']) ?></div>
                <div class="tq-module-footer">
                  <?php if ($att_row && $att_row['total_questions'] > 0): ?>
                  <span class="tq-badge tq-badge-navy"><i class="bi bi-arrow-repeat me-1"></i><?= $attempts ?> / 3</span>
                  <?php endif; ?>
                  <span class="tq-badge <?= $status_class ?>"><?= $status ?></span>
                </div>
              </div>
              <div class="tq-steps">
                <div class="tq-step <?= $step >= 1 ? 'active' : '' ?>"><span class="tq-step-num">1</span> Watch Video</div>
                <span class="tq-step-arrow">›</span>
                <div class="tq-step <?= $step >= 2 ? 'active' : '' ?>"><span class="tq-step-num">2</span> Take Exam</div>
                <span class="tq-step-arrow">›</span>
                <div class="tq-step <?= $step >= 3 ? 'done' : '' ?>"><span class="tq-step-num">3</span> Results</div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- ── Completed tab ── -->
        <div id="tab-completed" style="display:none;">
          <?php if (empty($done_modules)): ?>
          <div class="tq-empty">
            <span class="tq-empty-icon"><i class="bi bi-hourglass-split"></i></span>
            <div class="tq-empty-text">No completed modules yet</div>
            <div class="tq-empty-sub">Finish a module to see it here.</div>
          </div>
          <?php else: ?>
          <div class="tq-module-grid">
            <?php foreach ($done_modules as $module): ?>
            <div class="tq-module-card" onclick="location.href='view_module.php?id=<?= $module['id'] ?>'">
              <div style="position:relative;overflow:hidden;">
                <img src="Images/<?= htmlspecialchars($module['image'] ?: 'Module1.jpg') ?>" class="tq-module-img">
                <div style="position:absolute;inset:0;background:rgba(22,163,74,0.4);display:flex;align-items:center;justify-content:center;">
                  <i class="bi bi-check-circle-fill" style="font-size:40px;color:#fff;"></i>
                </div>
              </div>
              <div class="tq-module-body">
                <div class="tq-module-title"><?= htmlspecialchars($module['title']) ?></div>
                <div class="tq-module-desc"><?= htmlspecialchars($module['description']) ?></div>
                <div class="tq-module-footer">
                  <span class="tq-badge tq-badge-success"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>
                  <span class="tq-badge tq-badge-navy">Review</span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <?php endif; ?>
      </section>

      <!-- ═══════════ GAUGE SECTION ═══════════ -->
      <section class="tq-section" id="section-gauge">
        <div class="tq-section-header"><i class="bi bi-clipboard-check-fill"></i>Attribute Gauge R&amp;R Study</div>
        <div style="max-width:720px;">
          <?php
          $has_gauge = false;
          foreach ($modules as $module):
            if (stripos($module['title'], 'Gauge') === false) continue;
            $has_gauge = true;
            $mod_id   = (int)$module['id'];
            $chk_att  = $conn->query("SELECT attempts FROM exam_results WHERE username = '$user' AND module_id = $mod_id");
            $attempts = ($chk_att && $chk_att->num_rows > 0) ? (int)$chk_att->fetch_assoc()['attempts'] : 0;
            $is_locked = ($attempts >= 3);
          ?>
          <div class="tq-card" style="border-left:5px solid <?= $is_locked ? 'var(--tq-danger)' : 'var(--tq-navy)' ?>;">
            <div class="tq-card-body" style="display:flex; justify-content:space-between; align-items:center; gap:16px;">
              <div>
                <div style="font-size:15px; font-weight:600; color:var(--tq-navy);"><?= htmlspecialchars($module['title']) ?></div>
                <span class="tq-badge <?= $is_locked ? 'tq-badge-danger' : 'tq-badge-navy' ?>" style="margin-top:8px; display:inline-block;">
                  <i class="bi bi-arrow-repeat me-1"></i>Attempts: <?= $attempts ?> / 3
                </span>
              </div>
              <?php if ($is_locked): ?>
              <button class="btn-tq-primary" disabled style="opacity:0.5;">
                <i class="bi bi-lock-fill"></i> Locked
              </button>
              <?php else: ?>
              <a href="gauge_study.php?id=<?= $module['id'] ?>" class="btn-tq-gold">
                <i class="bi bi-clipboard-check"></i> Take Exam
              </a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (!$has_gauge): ?>
          <div class="tq-empty">
            <span class="tq-empty-icon"><i class="bi bi-clipboard-x"></i></span>
            <div class="tq-empty-text">No gauge exams assigned</div>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- ═══════════ RESULTS SECTION ═══════════ -->
      <section class="tq-section" id="section-results">
        <div class="tq-section-header"><i class="bi bi-bar-chart-fill"></i>My Exam History</div>
        <div class="tq-filter-pills">
          <button class="tq-pill active" data-filter="all">All Results</button>
          <button class="tq-pill" data-filter="pass">Passed</button>
          <button class="tq-pill" data-filter="fail">Failed</button>
        </div>
        <div class="tq-card" style="overflow:hidden;">
          <div style="overflow-x:auto;">
            <table class="tq-table">
              <thead>
                <tr>
                  <th>Module</th><th>Score</th><th>Type</th><th>Attempts</th><th>Status</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $res_query  = $conn->query("SELECT r.*, m.title FROM exam_results r JOIN modules m ON r.module_id = m.id WHERE r.username = '$user' AND r.total_questions > 0 ORDER BY r.id DESC");
                $has_results = false;
                while ($row = $res_query->fetch_assoc()):
                  $has_results = true;
                  $percent  = ($row['total_questions'] > 0) ? ($row['score'] / $row['total_questions']) * 100 : 0;
                  $is_gauge = ($row['total_questions'] == 50);
                  $view_link = $is_gauge ? "view_gauge_mistakes.php?module_id={$row['module_id']}" : "view_mistakes.php?module_id={$row['module_id']}";
                  $row_status = $percent >= 70 ? 'pass' : 'fail';
                ?>
                <tr class="tq-result-row <?= $percent >= 70 ? 'row-pass' : 'row-fail' ?>" data-status="<?= $row_status ?>">
                  <td style="font-weight:500;"><?= htmlspecialchars($row['title']) ?></td>
                  <td><?= $row['score'] ?> / <?= $row['total_questions'] ?></td>
                  <td><span class="tq-badge tq-badge-navy"><?= $is_gauge ? 'Gauge' : 'Quiz' ?></span></td>
                  <td><?= $row['attempts'] ?> / 3</td>
                  <td><span class="tq-badge <?= $percent >= 70 ? 'tq-badge-success' : 'tq-badge-danger' ?>"><?= $percent >= 70 ? 'Passed' : 'Failed' ?></span></td>
                  <td>
                    <?php if (!empty($row['wrong_questions'])): ?>
                    <a href="<?= $view_link ?>" class="btn-tq-gold" style="padding:6px 14px; font-size:12px;">
                      <i class="bi bi-eye"></i> Review
                    </a>
                    <?php else: ?>
                    <span class="tq-badge tq-badge-success"><i class="bi bi-star-fill me-1"></i>Perfect!</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$has_results): ?>
                <tr><td colspan="6">
                  <div class="tq-empty" style="padding:32px;">
                    <span class="tq-empty-icon"><i class="bi bi-inbox"></i></span>
                    <div class="tq-empty-text">No exam history yet</div>
                    <div class="tq-empty-sub">Complete a module to see your results here.</div>
                  </div>
                </td></tr>
                <?php endif; ?>
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
function switchModuleTab(btn, targetId) {
    document.querySelectorAll('.tq-mtab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    ['tab-enrolled', 'tab-completed'].forEach(function(id) {
        document.getElementById(id).style.display = id === targetId ? '' : 'none';
    });
}
</script>
</body>
</html>
