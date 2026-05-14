<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$username  = $_SESSION['username'];

$mod_query = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$module    = $mod_query->fetch_assoc();
$avatar    = strtoupper(substr($username, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gauge Study — <?= htmlspecialchars($module['title']) ?></title>
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
        <li class="tq-nav-item active"><a href="EMPLOYEE.php?tab=gauge" class="tq-nav-link"><i class="bi bi-clipboard-check-fill"></i><span>Attribute Gauge R&amp;R Study</span></a></li>
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=results" class="tq-nav-link"><i class="bi bi-bar-chart-fill"></i><span>My Results</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>

    <main class="tq-content">
      <div style="max-width:700px; margin:0 auto;">

        <div class="tq-section-header"><i class="bi bi-clipboard-check-fill"></i><?= htmlspecialchars($module['title']) ?></div>

        <!-- Info card -->
        <div class="tq-card" style="margin-bottom:20px; border-left:5px solid var(--tq-navy);">
          <div class="tq-card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px;">
            <div><span style="color:var(--tq-muted);">Appraiser:</span> <strong><?= htmlspecialchars($username) ?></strong></div>
            <div><span style="color:var(--tq-muted);">Date:</span> <strong><?= date('Y-m-d') ?></strong></div>
            <div><span style="color:var(--tq-muted);">Process:</span> <strong><?= htmlspecialchars($module['title']) ?></strong></div>
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
                  <th>Trial 1</th>
                  <th>Trial 2</th>
                </tr>
              </thead>
              <tbody>
                <?php for ($i = 1; $i <= 50; $i++): ?>
                <tr>
                  <td style="font-weight:700; color:var(--tq-navy);"><?= $i ?></td>
                  <td>
                    <!-- Hidden real inputs -->
                    <input type="hidden" name="q<?= $i ?>_t1" id="q<?= $i ?>_t1_val" value="">
                    <div class="tq-toggle-wrap">
                      <button type="button" class="tq-toggle-btn" data-val="1" data-target="q<?= $i ?>_t1_val">1</button>
                      <button type="button" class="tq-toggle-btn" data-val="0" data-target="q<?= $i ?>_t1_val">0</button>
                    </div>
                  </td>
                  <td>
                    <input type="hidden" name="q<?= $i ?>_t2" id="q<?= $i ?>_t2_val" value="">
                    <div class="tq-toggle-wrap">
                      <button type="button" class="tq-toggle-btn" data-val="1" data-target="q<?= $i ?>_t2_val">1</button>
                      <button type="button" class="tq-toggle-btn" data-val="0" data-target="q<?= $i ?>_t2_val">0</button>
                    </div>
                  </td>
                </tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>

          <div style="position:sticky; bottom:0; background:#fff; padding:16px 0; border-top:2px solid #eef0f7; margin-top:4px;">
            <button type="submit" class="btn-tq-gold" style="width:100%; justify-content:center; height:50px; font-size:15px;">
              <i class="bi bi-send-check"></i> Submit Study Results
            </button>
          </div>
        </form>

      </div>
    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
<script>
// Validate all toggles filled before submit
document.querySelector('form').addEventListener('submit', function(e) {
  const hidden = this.querySelectorAll('input[type="hidden"][name^="q"]');
  let missing = false;
  hidden.forEach(function(inp) { if (inp.value === '') missing = true; });
  if (missing) {
    e.preventDefault();
    tqToast('Please fill in all 50 items before submitting.', 'error');
  }
});
</script>
</body>
</html>
