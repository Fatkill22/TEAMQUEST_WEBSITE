<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') { die("Access Denied."); }

$module_id   = (int)$_GET['id'];
$mod_query   = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$module_title = ($mod_query->fetch_assoc())['title'] ?? "Unknown Study";

$key_query = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
$master_key = []; $total_good = 0; $total_bad = 0;
while ($row = $key_query->fetch_assoc()) {
    $master_key[$row['question_num']] = $row['correct_val'];
    if ($row['correct_val'] == 1) $total_good++;
    else $total_bad++;
}

$results_query = $conn->query("SELECT username, score, wrong_questions FROM exam_results WHERE module_id = $module_id ORDER BY score DESC");
$all_rows      = $results_query ? $results_query->fetch_all(MYSQLI_ASSOC) : [];

// Users who have all 3 attempt details stored (eligible for Excel export)
$detail_counts = [];
$dc_res = $conn->query("SELECT username, COUNT(*) as cnt FROM gauge_attempt_details WHERE module_id = $module_id GROUP BY username");
if ($dc_res) while ($r = $dc_res->fetch_assoc()) $detail_counts[$r['username']] = (int)$r['cnt'];

function effColor($v)  { return $v >= 90 ? 'tq-badge-success' : ($v >= 80 ? 'tq-badge-warning' : 'tq-badge-danger'); }
function missColor($v) { return $v <= 2  ? 'tq-badge-success' : ($v <= 5  ? 'tq-badge-warning' : 'tq-badge-danger'); }
function alarmColor($v){ return $v <= 5  ? 'tq-badge-success' : ($v <= 10 ? 'tq-badge-warning' : 'tq-badge-danger'); }
function rating($type, $v) {
    if ($type==='eff')   return $v>=90 ? 'Acceptable' : ($v>=80 ? 'Marginal' : 'Unacceptable');
    if ($type==='miss')  return $v<=2  ? 'Acceptable' : ($v<=5  ? 'Marginal' : 'Unacceptable');
    if ($type==='alarm') return $v<=5  ? 'Acceptable' : ($v<=10 ? 'Marginal' : 'Unacceptable');
}

// Count summary for donut chart
$cnt_accept = 0; $cnt_marginal = 0; $cnt_unaccept = 0;
foreach ($all_rows as $r) {
    $eff = ($r['score'] / 50) * 100;
    $rat = rating('eff', $eff);
    if ($rat === 'Acceptable')   $cnt_accept++;
    elseif ($rat === 'Marginal') $cnt_marginal++;
    else                         $cnt_unaccept++;
}

$admin_user = $_SESSION['username'];
$avatar     = strtoupper(substr($admin_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GR&R Report — <?= htmlspecialchars($module_title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    .modal-table th { position:sticky; top:0; background:var(--tq-navy); color:#fff; z-index:10; }
  </style>
</head>
<body>
<div class="tq-shell">

  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">GR&amp;R Report</span>
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
        <li class="tq-nav-item active"><a href="admin_modules.php?tab=gauge-admin" class="tq-nav-link"><i class="bi bi-clipboard-data-fill"></i><span>AR&amp;R Study</span></a></li>
        <li class="tq-nav-item"><a href="admin_modules.php?tab=users" class="tq-nav-link"><i class="bi bi-people-fill"></i><span>User Attempts</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>

    <main class="tq-content">

      <!-- Report Header -->
      <div class="tq-card" style="margin-bottom:20px; overflow:hidden;">
        <div class="tq-report-header">
          <div>
            <div style="font-size:17px; font-weight:700;">Attribute Measurement System Analysis (GR&amp;R)</div>
            <div style="font-size:13px; opacity:.75; margin-top:4px;">Process: <?= htmlspecialchars($module_title) ?></div>
          </div>
          <div style="display:flex; gap:10px; align-items:center;">
            <span class="tq-badge tq-badge-success"><i class="bi bi-check-circle me-1"></i>Good Parts: <?= $total_good ?></span>
            <span class="tq-badge tq-badge-danger"><i class="bi bi-x-circle me-1"></i>Bad Parts: <?= $total_bad ?></span>
          </div>
        </div>
      </div>

      <?php if ($total_good == 0 && $total_bad == 0): ?>
      <div class="tq-card">
        <div class="tq-card-body">
          <div class="tq-empty">
            <span class="tq-empty-icon"><i class="bi bi-key-fill"></i></span>
            <div class="tq-empty-text">Master Answer Key not set</div>
            <div class="tq-empty-sub">Go to Answer Key to configure the correct values.</div>
            <a href="gauge_control.php?id=<?= $module_id ?>" class="btn-tq-gold" style="margin-top:16px;">Set Answer Key</a>
          </div>
        </div>
      </div>

      <?php elseif (empty($all_rows)): ?>
      <div class="tq-card">
        <div class="tq-card-body">
          <div class="tq-empty">
            <span class="tq-empty-icon"><i class="bi bi-person-x"></i></span>
            <div class="tq-empty-text">No appraisers have taken this study yet</div>
          </div>
        </div>
      </div>

      <?php else: ?>

      <!-- Summary Donut + Stats Row -->
      <div style="display:grid; grid-template-columns:220px 1fr; gap:20px; margin-bottom:20px;">
        <div class="tq-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px;">
          <div style="font-size:12px; font-weight:700; color:var(--tq-muted); text-transform:uppercase; margin-bottom:12px;">Appraiser Summary</div>
          <canvas id="gaugeDonut" width="160" height="160"></canvas>
          <div style="margin-top:14px; font-size:12px; display:flex; flex-direction:column; gap:6px; width:100%;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span><span style="display:inline-block;width:10px;height:10px;background:#16a34a;border-radius:50%;margin-right:6px;"></span>Acceptable</span>
              <strong><?= $cnt_accept ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span><span style="display:inline-block;width:10px;height:10px;background:#f59e0b;border-radius:50%;margin-right:6px;"></span>Marginal</span>
              <strong><?= $cnt_marginal ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span><span style="display:inline-block;width:10px;height:10px;background:#dc2626;border-radius:50%;margin-right:6px;"></span>Unacceptable</span>
              <strong><?= $cnt_unaccept ?></strong>
            </div>
          </div>
        </div>

        <div class="tq-card" style="overflow:hidden;">
          <div class="tq-card-header"><i class="bi bi-table me-2" style="color:var(--tq-gold);"></i>Appraiser Results</div>
          <div style="overflow-x:auto;">
            <?php $modals_html = ""; ?>
            <table class="tq-table">
              <thead>
                <tr>
                  <th rowspan="2" style="vertical-align:middle;">Appraiser</th>
                  <th rowspan="2" style="vertical-align:middle; text-align:center;">Score</th>
                  <th colspan="3" style="text-align:center; border-bottom:1px solid rgba(255,255,255,0.2);">Metrics</th>
                  <th colspan="3" style="text-align:center; border-bottom:1px solid rgba(255,255,255,0.2);">Rating</th>
                </tr>
                <tr>
                  <th style="text-align:center;">Effectiveness</th>
                  <th style="text-align:center;">Miss Rate</th>
                  <th style="text-align:center;">False Alarm</th>
                  <th style="text-align:center;">Eff.</th>
                  <th style="text-align:center;">Miss</th>
                  <th style="text-align:center;">Alarm</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($all_rows as $row):
                  $score      = $row['score'];
                  $uname      = $row['username'];
                  $uname_safe = preg_replace('/[^a-zA-Z0-9]/', '', $uname);
                  $mistakes   = json_decode($row['wrong_questions'], true);
                  $miss_list = []; $false_alarm_list = [];
                  if (is_array($mistakes)) {
                      foreach ($mistakes as $q_num => $user_ans) {
                          if ($user_ans == 1) $miss_list[]        = $q_num;
                          if ($user_ans == 0) $false_alarm_list[] = $q_num;
                      }
                  }
                  $miss_count      = count($miss_list);
                  $false_alarm_count = count($false_alarm_list);
                  $correct_count   = 50 - ($miss_count + $false_alarm_count);
                  $effectiveness   = ($score / 50) * 100;
                  $miss_rate       = ($total_bad  > 0) ? ($miss_count        / $total_bad)  * 100 : 0;
                  $false_alarm_rate= ($total_good > 0) ? ($false_alarm_count / $total_good) * 100 : 0;
                  $rat_eff   = rating('eff',   $effectiveness);
                  $rat_miss  = rating('miss',  $miss_rate);
                  $rat_alarm = rating('alarm', $false_alarm_rate);
                  $row_bg = $rat_eff === 'Acceptable' ? '' : ($rat_eff === 'Marginal' ? 'style="background:#fefce8;"' : 'style="background:#fff5f5;"');
                ?>
                <tr <?= $row_bg ?>>
                  <td style="font-weight:600;"><?= htmlspecialchars($uname) ?></td>
                  <td style="text-align:center;">
                    <div><?= $score ?> / 50</div>
                    <button class="btn-tq-outline" style="padding:4px 10px; font-size:11px; margin-top:4px;"
                            data-bs-toggle="modal" data-bs-target="#modal_<?= $uname_safe ?>">
                      <i class="bi bi-search"></i> Breakdown
                    </button>
                  </td>
                  <td style="text-align:center; font-weight:700;"><?= number_format($effectiveness,1) ?>%</td>
                  <td style="text-align:center; font-weight:700;"><?= number_format($miss_rate,1) ?>%</td>
                  <td style="text-align:center; font-weight:700;"><?= number_format($false_alarm_rate,1) ?>%</td>
                  <td style="text-align:center;"><span class="tq-badge <?= effColor($effectiveness) ?>"><?= $rat_eff ?></span></td>
                  <td style="text-align:center;"><span class="tq-badge <?= missColor($miss_rate) ?>"><?= $rat_miss ?></span></td>
                  <td style="text-align:center;"><span class="tq-badge <?= alarmColor($false_alarm_rate) ?>"><?= $rat_alarm ?></span></td>
                </tr>

                <?php
                // Build modal HTML
                ob_start(); ?>
                <div class="modal fade" id="modal_<?= $uname_safe ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-scrollable modal-lg">
                    <div class="modal-content">
                      <div class="modal-header" style="background:var(--tq-navy); color:#fff;">
                        <h5 class="modal-title">Breakdown: <?= htmlspecialchars($uname) ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body" style="background:#f5f6fa;">
                        <div style="display:flex; gap:16px; margin-bottom:16px;">
                          <div class="tq-stat-card" style="flex:1; border-top-color:var(--tq-success);">
                            <div class="tq-stat-icon" style="background:#dcfce7; color:var(--tq-success);"><i class="bi bi-check-lg"></i></div>
                            <div><div class="tq-stat-value" style="font-size:20px;"><?= $correct_count ?></div><div class="tq-stat-label">Correct</div></div>
                          </div>
                          <div class="tq-stat-card" style="flex:1; border-top-color:var(--tq-danger);">
                            <div class="tq-stat-icon" style="background:#fee2e2; color:var(--tq-danger);"><i class="bi bi-x-lg"></i></div>
                            <div><div class="tq-stat-value" style="font-size:20px;"><?= $miss_count ?></div><div class="tq-stat-label">Misses</div></div>
                          </div>
                          <div class="tq-stat-card" style="flex:1; border-top-color:var(--tq-warning);">
                            <div class="tq-stat-icon" style="background:#fef3c7; color:var(--tq-warning);"><i class="bi bi-exclamation-triangle"></i></div>
                            <div><div class="tq-stat-value" style="font-size:20px;"><?= $false_alarm_count ?></div><div class="tq-stat-label">False Alarms</div></div>
                          </div>
                        </div>
                        <table class="tq-table modal-table">
                          <thead><tr><th style="text-align:center;">Trial #</th><th style="text-align:center;">Master Key</th><th style="text-align:center;">User Answer</th><th style="text-align:center;">Status</th></tr></thead>
                          <tbody>
                            <?php for ($i = 1; $i <= 50; $i++):
                              $master_ans = $master_key[$i] ?? '-';
                              $user_ans   = $master_ans;
                              $status     = '<span class="tq-badge tq-badge-success">Correct</span>';
                              if (in_array($i, $miss_list))        { $user_ans = 1; $status = '<span class="tq-badge tq-badge-danger">MISS</span>'; }
                              elseif (in_array($i, $false_alarm_list)) { $user_ans = 0; $status = '<span class="tq-badge tq-badge-warning">FALSE ALARM</span>'; }
                            ?>
                            <tr style="<?= in_array($i,$miss_list)?'background:#fff5f5;':(in_array($i,$false_alarm_list)?'background:#fefce8;':'') ?>">
                              <td style="text-align:center; font-weight:700;"><?= $i ?></td>
                              <td style="text-align:center; font-weight:700;"><?= $master_ans ?></td>
                              <td style="text-align:center; font-weight:700;"><?= $user_ans ?></td>
                              <td style="text-align:center;"><?= $status ?></td>
                            </tr>
                            <?php endfor; ?>
                          </tbody>
                        </table>
                      </div>
                      <div class="modal-footer"><button class="btn-tq-outline" data-bs-dismiss="modal">Close</button></div>
                    </div>
                  </div>
                </div>
                <?php $modals_html .= ob_get_clean(); ?>

                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?= $modals_html ?>

      <?php
      $eligible = array_filter(array_column($all_rows, 'username'), function($u) use ($detail_counts) {
          return ($detail_counts[$u] ?? 0) >= 3;
      });
      $eligible = array_values($eligible);
      ?>

      <!-- Excel Export Section -->
      <div class="tq-card" style="margin-top:20px; border-left:5px solid var(--tq-gold);">
        <div class="tq-card-header">
          <i class="bi bi-file-earmark-excel-fill me-2" style="color:var(--tq-gold);"></i>
          Export to Excel — Attribute GR&amp;R Template
        </div>
        <div class="tq-card-body">
          <?php if (empty($eligible)): ?>
          <div class="tq-empty" style="padding:20px 0;">
            <span class="tq-empty-icon"><i class="bi bi-hourglass-split"></i></span>
            <div class="tq-empty-text">No appraisers have completed all 3 attempts yet</div>
            <div class="tq-empty-sub">Once 3 appraisers each finish 3 exam attempts, you can export their data to the Excel template.</div>
          </div>
          <?php else: ?>
          <p style="font-size:13px; color:var(--tq-muted); margin-bottom:16px;">
            Select exactly 3 appraisers with completed attempts. They will fill <strong>Appraiser A</strong>, <strong>B</strong>, and <strong>C</strong> columns in the Excel template in the order you check them.
          </p>
          <form action="export_gauge_excel.php" method="POST">
            <input type="hidden" name="module_id" value="<?= $module_id ?>">
            <input type="hidden" name="appraiser_a" id="exp_a" value="">
            <input type="hidden" name="appraiser_b" id="exp_b" value="">
            <input type="hidden" name="appraiser_c" id="exp_c" value="">

            <div id="export-user-list" style="display:flex; flex-direction:column; gap:8px; margin-bottom:20px;">
              <?php foreach ($eligible as $eu): ?>
              <label class="export-user-row" style="display:flex; align-items:center; gap:12px; padding:10px 14px; border:1px solid #dde0ee; border-radius:8px; cursor:pointer; transition:border-color .15s;">
                <input type="checkbox" class="exp-check" value="<?= htmlspecialchars($eu) ?>" style="width:17px; height:17px; cursor:pointer; accent-color:var(--tq-navy);">
                <span style="font-weight:600; flex:1;"><?= htmlspecialchars($eu) ?></span>
                <span class="exp-role-badge" style="display:none; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; background:var(--tq-gold); color:#fff;"></span>
                <span class="tq-badge tq-badge-success" style="font-size:11px;"><i class="bi bi-check-circle me-1"></i>3 attempts</span>
              </label>
              <?php endforeach; ?>
            </div>

            <button type="submit" id="exp-submit-btn" class="btn-tq-gold" disabled style="width:100%; justify-content:center; height:46px; font-size:14px;">
              <i class="bi bi-file-earmark-arrow-down-fill"></i>
              <span id="exp-btn-label">Export to Excel (0 / 3 selected)</span>
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <?php endif; ?>

      <a href="admin_modules.php?tab=gauge-admin" class="btn-tq-outline" style="margin-top:16px;">
        <i class="bi bi-arrow-left"></i> Back to Admin
      </a>

    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/scripts.js"></script>
<script>
(function () {
  var checks  = document.querySelectorAll('.exp-check');
  var expA    = document.getElementById('exp_a');
  var expB    = document.getElementById('exp_b');
  var expC    = document.getElementById('exp_c');
  var expBtn  = document.getElementById('exp-submit-btn');
  var expLbl  = document.getElementById('exp-btn-label');
  if (!checks.length) return;

  var roles   = ['Appraiser A', 'Appraiser B', 'Appraiser C'];
  var colors  = ['#1a3a6b', '#c8960c', '#16a34a'];

  function refresh() {
    var selected = [];
    checks.forEach(function (cb) { if (cb.checked) selected.push(cb.value); });

    // Assign hidden inputs
    if (expA) expA.value = selected[0] || '';
    if (expB) expB.value = selected[1] || '';
    if (expC) expC.value = selected[2] || '';

    // Update visual badges on each row
    checks.forEach(function (cb) {
      var row   = cb.closest('.export-user-row');
      var badge = row.querySelector('.exp-role-badge');
      var idx   = selected.indexOf(cb.value);
      if (idx >= 0) {
        badge.textContent = roles[idx];
        badge.style.background = colors[idx];
        badge.style.display = '';
        row.style.borderColor = colors[idx];
      } else {
        badge.style.display = 'none';
        row.style.borderColor = '#dde0ee';
      }
    });

    var n = selected.length;
    if (expBtn) expBtn.disabled = (n !== 3);
    if (expLbl) expLbl.textContent = 'Export to Excel (' + n + ' / 3 selected)';
  }

  checks.forEach(function (cb) {
    cb.addEventListener('change', function () {
      var selected = [];
      checks.forEach(function (c) { if (c.checked) selected.push(c.value); });
      if (selected.length > 3) { this.checked = false; }
      refresh();
    });
  });
})();
</script>
<script>
var ctx = document.getElementById('gaugeDonut');
if (ctx) {
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Acceptable', 'Marginal', 'Unacceptable'],
      datasets: [{
        data: [<?= $cnt_accept ?>, <?= $cnt_marginal ?>, <?= $cnt_unaccept ?>],
        backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'],
        borderWidth: 2, borderColor: '#fff'
      }]
    },
    options: {
      cutout: '65%',
      plugins: { legend: { display: false } },
      responsive: false
    }
  });
}
</script>
</body>
</html>
