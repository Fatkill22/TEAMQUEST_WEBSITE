<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

$dept = $_SESSION['department'];
$user = $_SESSION['username'];

// Fetch Modules for the department
$modules = [];
$stmt = $conn->prepare(
    "SELECT * FROM modules 
     WHERE department = ? OR department = 'all' 
     ORDER BY id DESC"
);

$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row;
    }
}
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
        .Homepage-Tab {
            background-image: url("Images/TeamQuest.jpg");
            background-size: cover;
            background-position: center;
            min-height: 800px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .Homepage-button-tab .btn { font-size: 28px; padding: 20px 60px; }
        .module-card {
            border: 2px solid rgb(0, 0, 0);
            border-radius: 10px;
            overflow: hidden;
            text-align: center;
            background-color: #f9f9f9;
            transition: transform 0.3s;
            height: 100%;
        }
        .module-card:hover { transform: scale(1.05); }
        .module-card img { width: 100%; height: 250px; object-fit: cover; }
        .module-description { padding: 15px; }
        .nav-tabs .nav-link { color: #000; font-weight: bold; }
        .nav-tabs .nav-link.active { background-color: #e9ecef; }
        .gauge-list-item {
            border: 1px solid #000;
            border-left: 10px solid #000;
            background: #fff;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body class="bg-light">

<div class="logo text-white">
    <img src="Images/Logo.png" alt="Company Logo">
</div>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab">Home</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#modules" type="button" role="tab">Normal Modules</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">Gauge Exams</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#my-results" type="button" role="tab">My Results</button>
    </li>
    <li class="nav-item ms-auto">
        <a href="INDEX.html" class="nav-link text-danger">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    </li>
</ul>

<div class="tab-content" id="myTabContent">

    <div class="tab-pane fade show active" id="home" role="tabpanel">
        <div class="Homepage-Tab">
            <div class="Homepage-button-tab">
                <button type="button"
                        class="btn btn-primary shadow-lg"
                        onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('button[data-bs-target=\'#modules\']')).show()">
                    Start Online Training
                </button>
            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="modules" role="tabpanel">
        <div class="container">
            <h2 class="mb-4">Training Modules: <?php echo htmlspecialchars($dept); ?></h2>
            <div class="row">
                <?php if (!empty($modules)) { 
                    foreach ($modules as $module) { 
                        // LOGIC: Hide Gauge Exams from this tab
                        if (stripos($module['title'], 'Gauge') !== false) continue; 
                ?>
                        <div class="col-md-6 mb-4">
                            <div class="module-card shadow-sm" style="cursor:pointer;" onclick="window.location.href='view_module.php?id=<?php echo $module['id']; ?>'">
                                <?php if (!empty($module['image'])) { ?>
                                    <img src="Images/<?php echo $module['image']; ?>" alt="Module Image">
                                <?php } else { ?>
                                    <div class="bg-secondary py-5 text-white">No Image Available</div>
                                <?php } ?>
                                <div class="module-description">
                                    <h4 class="fw-bold"><?php echo htmlspecialchars($module['title']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($module['description']); ?></p>
                                    <span class="btn btn-sm btn-dark">Open Module</span>
                                </div>
                            </div>
                        </div>
                <?php } } else { ?>
                    <div class="col-12 text-center mt-5"><p class="text-muted fs-4">No modules available.</p></div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="performance" role="tabpanel">
        <div class="container py-5">
            <h3 class="text-center fw-bold mb-5">ATTRIBUTE GAUGE R&R STUDY</h3>
            <div class="row justify-content-center">
                <div class="col-md-8 mx-auto">
                <?php 
                $has_gauge = false;
                foreach ($modules as $module) { 
                    // LOGIC: Only show Gauge Exams in this tab
                    if (stripos($module['title'], 'Gauge') === false) continue; 
                    $has_gauge = true;
                ?>
                    <div class="gauge-list-item shadow-sm">
                        <div>
                            <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($module['title']); ?></h5>
                            <small class="text-muted">Ref: AD-0001-F4-Rev 2</small>
                        </div>
                        <a href="gauge_study.php?id=<?php echo $module['id']; ?>" class="btn btn-dark fw-bold">TAKE GAUGE EXAM</a>
                    </div>
                <?php } 
                
                // Show message if no Gauge exams exist yet
                if (!$has_gauge) {
                    echo "<p class='text-center text-muted border p-4 bg-white'>No Gauge Studies assigned to your department yet.</p>";
                }
                ?>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="my-results" role="tabpanel">
        <div class="container">
            <h3 class="mb-4">My Exam History</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-striped bg-white align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Module Title</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Type</th>
                            <th>Attempts</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res_query = $conn->query(
                            "SELECT r.*, m.title 
                             FROM exam_results r 
                             JOIN modules m ON r.module_id = m.id 
                             WHERE r.username = '$user' 
                             ORDER BY r.id DESC"
                        );

                        if ($res_query && $res_query->num_rows > 0) {
                            while ($row = $res_query->fetch_assoc()) {
                                $percent = ($row['total_questions'] > 0) ? ($row['score'] / $row['total_questions']) * 100 : 0;
                                
                                // Feature: Identify if it was a Gauge Study (50 questions)
                                $is_gauge = ($row['total_questions'] == 50);
                                
                                $type_label = $is_gauge 
                                    ? '<span class="badge bg-dark">Gauge Exam</span>' 
                                    : '<span class="badge bg-secondary">Quiz</span>';

                                $status = ($percent >= 70)
                                    ? '<span class="badge bg-success">Passed</span>'
                                    : '<span class="badge bg-danger">Failed</span>';

                                // THE ROUTING FIX: Direct to correct mistakes page based on exam type
                                $view_link = $is_gauge 
                                    ? "view_gauge_mistakes.php?module_id={$row['module_id']}" 
                                    : "view_mistakes.php?module_id={$row['module_id']}";

                                // Show view button or perfect score label
                                if (!empty($row['wrong_questions'])) {
                                    $action_td = "<a href='{$view_link}' class='btn btn-sm btn-outline-danger'>View</a>";
                                } else {
                                    $action_td = "<span class='text-success small fw-bold'>Perfect Score!</span>";
                                }

                                echo "
                                <tr class='text-center'>
                                    <td class='text-start'>" . htmlspecialchars($row['title']) . "</td>
                                    <td>{$row['score']} / {$row['total_questions']}</td>
                                    <td>" . number_format($percent, 2) . "%</td>
                                    <td>$type_label</td>
                                    <td>{$row['attempts']} / 3</td>
                                    <td>$status</td>
                                    <td>$action_td</td>
                                </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>No exams taken yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>