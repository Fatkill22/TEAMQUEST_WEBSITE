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
$stmt = $conn->prepare("SELECT * FROM modules WHERE department = ? OR department = 'all' ORDER BY id DESC");
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $modules[] = $row; }
$stmt->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Training - TeamQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .logo img { height: 70px; width: auto; }
        .logo { background-color: blue; padding: 10px; }
        .Homepage-Tab { background-image: url("Images/TeamQuest.jpg"); background-size: cover; background-position: center; min-height: 800px; display: flex; justify-content: center; align-items: center; }
        .module-card { border: 2px solid rgb(0, 0, 0); border-radius: 10px; overflow: hidden; text-align: center; background-color: #f9f9f9; height: 100%; }
        .module-card img { width: 100%; height: 250px; object-fit: cover; }
        .nav-tabs .nav-link { color: #000; font-weight: bold; }
        .nav-tabs .nav-link.active { background-color: #e9ecef; }
        .gauge-list-item { border: 1px solid #000; border-left: 10px solid #000; background: #fff; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body class="bg-light">

<div class="logo text-white"><img src="Images/Logo.png" alt="Company Logo"></div>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#home">Home</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#modules">Normal Modules</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#performance">Gauge Exams</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#my-results">My Results</button></li>
    <li class="nav-item ms-auto"><a href="LOGOUT.php" class="nav-link text-danger">Logout (<?= htmlspecialchars($_SESSION['username']); ?>)</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="home">
        <div class="Homepage-Tab"><button class="btn btn-primary btn-lg" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('button[data-bs-target=\'#modules\']')).show()">Start Training</button></div>
    </div>

    <div class="tab-pane fade p-4" id="modules">
        <div class="container">
            <h2 class="mb-4">Training Modules: <?= htmlspecialchars($dept); ?></h2>
            <div class="row">
                <?php foreach ($modules as $module) { 
                    if (stripos($module['title'], 'Gauge') !== false) continue; 
                    $mod_id = (int)$module['id'];
                    $chk_att = $conn->query("SELECT attempts FROM exam_results WHERE username = '$user' AND module_id = $mod_id");
                    $attempts = ($chk_att && $chk_att->num_rows > 0) ? $chk_att->fetch_assoc()['attempts'] : 0;
                    $is_locked = ($attempts >= 3);
                ?>
                    <div class="col-md-6 mb-4">
                        <div class="module-card shadow-sm <?= $is_locked ? 'opacity-50' : '' ?>" <?= $is_locked ? '' : "style='cursor:pointer;' onclick=\"window.location.href='view_module.php?id={$module['id']}';\"" ?>>
                            <img src="Images/<?= $module['image'] ?: 'default.jpg'; ?>" style="<?= $is_locked ? 'filter: grayscale(100%);' : '' ?>">
                            <div class="p-3">
                                <h4 class="fw-bold"><?= htmlspecialchars($module['title']); ?></h4>
                                <p class="text-muted mb-2">Attempts: <?= $attempts ?> / 3</p>
                                <?php if ($is_locked): ?> <span class="btn btn-sm btn-danger disabled">Locked</span>
                                <?php else: ?> <span class="btn btn-sm btn-dark">Open Module</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="performance">
        <div class="container py-5">
            <h3 class="text-center fw-bold mb-5">ATTRIBUTE GAUGE R&R STUDY</h3>
            <div class="col-md-8 mx-auto">
                <?php foreach ($modules as $module) { 
                    if (stripos($module['title'], 'Gauge') === false) continue; 
                    $mod_id = (int)$module['id'];
                    $chk_att = $conn->query("SELECT attempts FROM exam_results WHERE username = '$user' AND module_id = $mod_id");
                    $attempts = ($chk_att && $chk_att->num_rows > 0) ? $chk_att->fetch_assoc()['attempts'] : 0;
                    $is_locked = ($attempts >= 3);
                ?>
                    <div class="gauge-list-item shadow-sm <?= $is_locked ? 'opacity-75 bg-light' : '' ?>">
                        <div>
                            <h5 class="mb-1 fw-bold"><?= htmlspecialchars($module['title']); ?></h5>
                            <small class="text-muted">Ref: AD-0001-F4-Rev 2 <br> <span class="<?= $is_locked ? 'text-danger fw-bold' : 'text-primary' ?>">Attempts: <?= $attempts ?> / 3</span></small>
                        </div>
                        <?php if ($is_locked): ?> <button class="btn btn-secondary fw-bold" disabled>LOCKED</button>
                        <?php else: ?> <a href="gauge_study.php?id=<?= $module['id']; ?>" class="btn btn-dark fw-bold shadow-sm">TAKE EXAM</a>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="my-results">
        <div class="container">
            <h3 class="mb-4">My Exam History</h3>
            <table class="table table-bordered bg-white align-middle text-center">
                <thead class="table-dark"><tr><th>Module</th><th>Score</th><th>Type</th><th>Attempts</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php
                    $res_query = $conn->query("SELECT r.*, m.title FROM exam_results r JOIN modules m ON r.module_id = m.id WHERE r.username = '$user' ORDER BY r.id DESC");
                    while ($row = $res_query->fetch_assoc()):
                        $percent = ($row['total_questions'] > 0) ? ($row['score'] / $row['total_questions']) * 100 : 0;
                        $is_gauge = ($row['total_questions'] == 50);
                        $view_link = $is_gauge ? "view_gauge_mistakes.php?module_id={$row['module_id']}" : "view_mistakes.php?module_id={$row['module_id']}";
                    ?>
                        <tr>
                            <td class="text-start"><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['score'] ?> / <?= $row['total_questions'] ?></td>
                            <td><?= $is_gauge ? '<span class="badge bg-dark">Gauge Exam</span>' : '<span class="badge bg-secondary">Quiz</span>' ?></td>
                            <td><?= $row['attempts'] ?> / 3</td>
                            <td><?= $percent >= 70 ? 'Passed' : 'Failed' ?></td>
                            <td><?= !empty($row['wrong_questions']) ? "<a href='{$view_link}' class='btn btn-sm btn-outline-danger'>View</a>" : "<span class='text-success small fw-bold'>Perfect!</span>" ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>