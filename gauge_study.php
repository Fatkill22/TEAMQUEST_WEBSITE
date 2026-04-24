<?php
session_start();
include "config.php";

$module_id = (int)$_GET['id'];
$mod_res   = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$mod_data  = $mod_res->fetch_assoc();
$username  = $_SESSION['username'] ?? 'Unknown';
$avatar    = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gauge Study — <?= htmlspecialchars($mod_data['title']) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="tq-shell">

  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">Attribute Gauge R&amp;R Study</span>
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
        <li class="tq-nav-item active"><a href="EMPLOYEE.php?tab=gauge" class="tq-nav-link"><i class="bi bi-clipboard-check-fill"></i><span>Gauge Exams</span></a></li>
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=results" class="tq-nav-link"><i class="bi bi-bar-chart-fill"></i><span>My Results</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>

    <main class="tq-content">
      <div style="max-width:660px; margin:0 auto;">

        <div class="tq-section-header"><i class="bi bi-clipboard-check-fill"></i><?= htmlspecialchars($mod_data['title']) ?></div>

        <div class="tq-card" style="margin-bottom:20px; border-left:5px solid var(--tq-navy);">
          <div class="tq-card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px;">
            <div><span style="color:var(--tq-muted);">Appraiser:</span> <strong><?= htmlspecialchars($username) ?></strong></div>
            <div><span style="color:var(--tq-muted);">Date:</span> <strong><?= date('Y-m-d') ?></strong></div>
            <div><span style="color:var(--tq-muted);">Process:</span> <strong><?= htmlspecialchars($mod_data['title']) ?></strong></div>
            <div><span style="color:var(--tq-muted);">Reference:</span> <strong>AD-0001-F4-Rev 2</strong></div>
          </div>
        </div>

        <div class="tq-card" style="margin-bottom:8px; background:rgba(200,150,12,0.1); border:none; box-shadow:none;">
          <div class="tq-card-body" style="padding:12px 20px; font-size:13px; font-weight:600; color:var(--tq-gold); text-align:center;">
            <i class="bi bi-info-circle me-1"></i>
            Enter <strong>1</strong> if the part is GOOD &nbsp;·&nbsp; Enter <strong>0</strong> if REJECT
          </div>
        </div>

        <form action="process_gauge.php" method="POST">
          <input type="hidden" name="module_id" value="<?= $module_id ?>">

          <div class="tq-card">
            <table class="tq-gauge-table">
              <thead>
                <tr>
                  <th style="width:60px;">#</th>
                  <th>Your Answer</th>
                  <th style="width:35%;">Remarks</th>
                </tr>
              </thead>
              <tbody>
                <?php for ($i = 1; $i <= 50; $i++): ?>
                <tr>
                  <td style="font-weight:700; color:var(--tq-navy);"><?= $i ?></td>
                  <td>
                    <input type="hidden" name="q_<?= $i ?>" id="q<?= $i ?>_val" value="">
                    <div class="tq-toggle-wrap">
                      <button type="button" class="tq-toggle-btn" data-val="1" data-target="q<?= $i ?>_val">1</button>
                      <button type="button" class="tq-toggle-btn" data-val="0" data-target="q<?= $i ?>_val">0</button>
                    </div>
                  </td>
                  <td style="color:var(--tq-muted); font-size:12px;"></td>
                </tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>

          <div style="position:sticky; bottom:0; background:#fff; padding:16px 0; border-top:2px solid #eef0f7; margin-top:4px;">
            <button type="submit" class="btn-tq-gold" style="width:100%; justify-content:center; height:50px; font-size:15px;">
              <i class="bi bi-send-check"></i> Submit Exam
            </button>
            <div style="text-align:center; margin-top:8px; font-size:11px; color:var(--tq-muted);">AD-0001-F4-Rev 2</div>
          </div>
        </form>

      </div>
    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
<script>
// Auto-advance to next row on toggle click
document.querySelectorAll('.tq-toggle-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    const wrap  = this.closest('tr');
    const next  = wrap.nextElementSibling;
    if (next) {
      const firstBtn = next.querySelector('.tq-toggle-btn');
      if (firstBtn) firstBtn.focus();
    }
  });
});
document.querySelector('form').addEventListener('submit', function(e) {
  const hidden = this.querySelectorAll('input[type="hidden"][name^="q_"]');
  let missing = false;
  hidden.forEach(function(inp) { if (inp.value === '') missing = true; });
  if (missing) {
    e.preventDefault();
    tqToast('Please answer all 50 items before submitting.', 'error');
  }
});
</script>
</body>
</html>
