<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
$username  = $_SESSION['username'];
$first     = isset($_SESSION['firstname']) ? $_SESSION['firstname'] : '';
$last      = isset($_SESSION['lastname'])  ? $_SESSION['lastname']  : '';
$full_name = trim($first . ' ' . $last) ?: $username;

$check         = $conn->query("SELECT attempts FROM exam_results WHERE username = '$username' AND module_id = $module_id");
$attempt_data  = $check->fetch_assoc();
$current_attempts = $attempt_data ? (int)$attempt_data['attempts'] : 0;

if ($current_attempts >= 3) {
    header("Location: EMPLOYEE.php?tab=modules");
    exit();
}

$questions = $conn->query("SELECT * FROM questions WHERE module_id = $module_id")->fetch_all(MYSQLI_ASSOC);

$mod_info  = $conn->query("SELECT title FROM modules WHERE id = $module_id")->fetch_assoc();
$mod_title = $mod_info ? $mod_info['title'] : 'Exam';

// ── Submission ────────────────────────────────────────────
if (isset($_POST['submit_exam'])) {
    $score    = 0;
    $total    = count($questions);
    $mistakes = [];
    $wrong_ids = [];

    foreach ($questions as $q) {
        $user_ans = $_POST['q_' . $q['id']] ?? null;
        if ($user_ans && $user_ans === $q['correct_option']) {
            $score++;
        } else {
            $option_key = $user_ans ? 'option_' . strtolower($user_ans) : null;
            $mistakes[] = [
                'question'         => $q['question_text'],
                'user_choice'      => $user_ans ?? 'None',
                'user_choice_text' => ($option_key && isset($q[$option_key])) ? $q[$option_key] : 'No Answer',
                'correct_option'   => $q['correct_option'],
                'correct_text'     => $q['option_' . strtolower($q['correct_option'])] ?? '',
                'options'          => ['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d']],
            ];
            $wrong_ids[] = $q['id'];
        }
    }

    $wrong_ids_str = implode(',', $wrong_ids);

    if ($attempt_data) {
        $stmt = $conn->prepare("UPDATE exam_results SET score=GREATEST(score,?), attempts=attempts+1, wrong_questions=? WHERE username=? AND module_id=?");
        $stmt->bind_param("issi", $score, $wrong_ids_str, $username, $module_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO exam_results (username,module_id,score,total_questions,attempts,wrong_questions) VALUES (?,?,?,?,1,?)");
        $stmt->bind_param("siiis", $username, $module_id, $score, $total, $wrong_ids_str);
    }
    $stmt->execute();

    $percent = $total > 0 ? round(($score / $total) * 100) : 0;
    $passed  = $percent >= 70;
    $avatar  = strtoupper(substr($username, 0, 1));
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Exam Results — TeamQuest</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="tq-shell">
  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">Exam Results</span>
    <div class="tq-user-badge">
      <div class="tq-avatar"><?= $avatar ?></div>
      <span><?= htmlspecialchars($username) ?></span>
    </div>
  </header>
  <div class="tq-body">
    <nav class="tq-sidebar">
      <ul class="tq-nav">
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=home" class="tq-nav-link"><i class="bi bi-house-fill"></i><span>Home</span></a></li>
        <li class="tq-nav-item active"><a href="EMPLOYEE.php?tab=modules" class="tq-nav-link"><i class="bi bi-book-fill"></i><span>My Modules</span></a></li>
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=gauge" class="tq-nav-link"><i class="bi bi-clipboard-check-fill"></i><span>Gauge Exams</span></a></li>
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=results" class="tq-nav-link"><i class="bi bi-bar-chart-fill"></i><span>My Results</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>
    <main class="tq-content">
      <div class="tq-result-wrap">

        <div class="tq-result-score-card <?= $passed ? 'passed' : 'failed' ?>">
          <div style="font-size:13px; color:var(--tq-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;"><?= htmlspecialchars($mod_title) ?></div>
          <div class="score-num"><?= $score ?>/<?= $total ?></div>
          <div class="score-label"><?= $passed ? '🎉 Passed!' : 'Failed — Review your mistakes below' ?></div>
          <div style="margin-top:12px; display:flex; justify-content:center; gap:20px; font-size:13px; color:var(--tq-muted);">
            <span><strong style="color:var(--tq-text);"><?= htmlspecialchars($full_name) ?></strong></span>
            <span>Attempt <strong style="color:var(--tq-text);"><?= $current_attempts + 1 ?></strong> / 3</span>
            <span>Score <strong style="color:var(--tq-text);"><?= $percent ?>%</strong></span>
          </div>
        </div>

        <?php if (!empty($mistakes)): ?>
        <div style="margin-bottom:16px;">
          <div class="tq-section-header" style="font-size:15px;"><i class="bi bi-x-circle-fill" style="color:var(--tq-danger);"></i>Review Your Mistakes</div>
          <?php foreach ($mistakes as $m): ?>
          <div class="tq-card" style="margin-bottom:12px;">
            <div class="tq-card-body">
              <div class="tq-mistake-question"><?= htmlspecialchars($m['question']) ?></div>
              <div class="tq-mistake-pair">
                <div class="tq-mistake-wrong">
                  <div class="tq-mistake-label"><i class="bi bi-x-circle-fill"></i> Your Answer</div>
                  <div class="tq-mistake-answer">
                    <strong><?= $m['user_choice'] ?>)</strong> <?= htmlspecialchars($m['user_choice_text']) ?>
                  </div>
                </div>
                <div class="tq-mistake-correct">
                  <div class="tq-mistake-label"><i class="bi bi-check-circle-fill"></i> Correct Answer</div>
                  <div class="tq-mistake-answer">
                    <strong><?= $m['correct_option'] ?>)</strong> <?= htmlspecialchars($m['correct_text']) ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px;">
          <a href="EMPLOYEE.php?tab=results" class="btn-tq-outline" style="flex:1; justify-content:center; padding:12px;">
            <i class="bi bi-bar-chart"></i> My Results
          </a>
          <a href="EMPLOYEE.php?tab=modules" class="btn-tq-primary" style="flex:1; justify-content:center; padding:12px;">
            <i class="bi bi-book"></i> Back to Modules
          </a>
        </div>

      </div>
    </main>
  </div>
</div>
<script src="assets/js/scripts.js"></script>
</body>
</html>
    <?php
    exit();
}
// ── End Submission ────────────────────────────────────────

$avatar = strtoupper(substr($username, 0, 1));
$total_q = count($questions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Take Exam — <?= htmlspecialchars($mod_title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="tq-shell">

  <header class="tq-topbar">
    <img src="Images/Logo.png" class="tq-logo" alt="TeamQuest">
    <span class="tq-page-title">Exam — <?= htmlspecialchars($mod_title) ?></span>
    <div class="tq-user-badge">
      <div class="tq-avatar"><?= $avatar ?></div>
      <span><?= htmlspecialchars($username) ?></span>
    </div>
  </header>

  <div class="tq-body">
    <nav class="tq-sidebar">
      <ul class="tq-nav">
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=home" class="tq-nav-link"><i class="bi bi-house-fill"></i><span>Home</span></a></li>
        <li class="tq-nav-item active"><a href="EMPLOYEE.php?tab=modules" class="tq-nav-link"><i class="bi bi-book-fill"></i><span>My Modules</span></a></li>
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=gauge" class="tq-nav-link"><i class="bi bi-clipboard-check-fill"></i><span>Gauge Exams</span></a></li>
        <li class="tq-nav-item"><a href="EMPLOYEE.php?tab=results" class="tq-nav-link"><i class="bi bi-bar-chart-fill"></i><span>My Results</span></a></li>
      </ul>
      <div class="tq-sidebar-footer"><a href="LOGOUT.php" class="tq-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
    </nav>

    <main class="tq-content">
      <div style="max-width:760px; margin:0 auto;">

        <div class="tq-exam-header">
          <span class="tq-exam-counter">
            <i class="bi bi-pencil-square me-2"></i>
            <?= htmlspecialchars($mod_title) ?> &nbsp;·&nbsp; Attempt <?= $current_attempts + 1 ?> / 3
          </span>
        </div>

        <form method="POST" id="exam-form">

          <?php foreach ($questions as $idx => $q): ?>
          <div class="tq-question-card" data-qindex="<?= $idx ?>">
            <div class="tq-question-num">Question <?= $idx + 1 ?> of <?= $total_q ?></div>
            <div class="tq-question-text"><?= htmlspecialchars($q['question_text']) ?></div>
            <?php foreach (['A','B','C','D'] as $opt): ?>
            <label class="tq-option">
              <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $opt ?>" required>
              <span class="tq-option-letter"><?= $opt ?></span>
              <span><?= htmlspecialchars($q['option_' . strtolower($opt)]) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>

          <!-- Sticky bottom bar -->
          <div class="tq-exam-sticky">
            <div class="tq-exam-dots">
              <?php for ($i = 0; $i < $total_q; $i++): ?>
              <div class="tq-dot" data-qindex="<?= $i ?>"></div>
              <?php endfor; ?>
            </div>
            <button type="submit" name="submit_exam" id="tq-submit-btn"
                    class="btn-tq-gold" disabled style="padding:10px 28px; font-size:15px;">
              <i class="bi bi-send-check"></i> Submit Exam
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
