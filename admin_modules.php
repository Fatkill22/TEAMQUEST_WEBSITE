<?php
session_start();
include "config.php";

// 1. Security Check
if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.php");
    exit();
}

// 2. Handle Adding Modules
if (isset($_POST['add'])) {
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $content     = $_POST['content'];
    $department  = $_POST['department'];

    $imageName = !empty($_FILES['image']['name']) ? time() . "_" . $_FILES['image']['name'] : "";
    $videoName = !empty($_FILES['video']['name']) ? time() . "_" . $_FILES['video']['name'] : "";

    if ($imageName) { move_uploaded_file($_FILES['image']['tmp_name'], "Images/" . $imageName); }
    if ($videoName) { move_uploaded_file($_FILES['video']['tmp_name'], "Videos/" . $videoName); }

    $stmt = $conn->prepare("INSERT INTO modules (title, description, content, image, video, department) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $title, $description, $content, $imageName, $videoName, $department);
    $stmt->execute();
    $stmt->close();
}

// 3. Handle Resetting Attempts
if (isset($_GET['reset_user']) && isset($_GET['mid'])) {
    $user_to_reset = $_GET['reset_user'];
    $mid           = (int)$_GET['mid'];
    $stmt = $conn->prepare("DELETE FROM exam_results WHERE username = ? AND module_id = ?");
    $stmt->bind_param("si", $user_to_reset, $mid);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_modules.php#users");
    exit();
}

// 4. Handle Deleting Modules
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM modules WHERE id=$id");
    header("Location: admin_modules.php");
    exit();
}

// 5. Fetch Modules
$modules = $conn->query("SELECT * FROM modules ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - TeamQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .logo img { height: 70px; width: auto; }
        .logo { background-color: blue; padding: 10px; }
        .Homepage-Tab { background-image: url("Images/TeamQuest.jpg"); background-size: cover; background-position: center; min-height: 500px; display: flex; justify-content: center; align-items: center; }
        .nav-tabs .nav-link { color: #000; font-weight: bold; }
        .nav-tabs .nav-link.active { background-color: #fff; border-bottom: none; }
        .admin-container { background-color: white; border-radius: 10px; padding: 30px; margin-top: 20px; border: 1px solid #ddd; }
        #userSearchInput { border: 2px solid #0d6efd; border-radius: 20px; }
    </style>
</head>
<body class="bg-light">

<div class="logo text-white"><img src="Images/Logo.png" alt="Company Logo"></div>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#home">Home</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#admin">Manage Modules</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#gauge-admin">Manage Gauge Study</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#users">User Attempts</button></li>
    <li class="nav-item ms-auto"><a href="INDEX.html" class="nav-link text-danger">Logout (Admin: <?= $_SESSION['username'] ?>)</a></li>
</ul>

<div class="tab-content">
    
    <div class="tab-pane fade show active" id="home">
        <div class="Homepage-Tab">
             <button type="button" class="btn btn-primary btn-lg shadow-lg" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('button[data-bs-target=\'#admin\']')).show()">Manage Training System</button>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="admin">
        <div class="container admin-container shadow-sm">
            <h2 class="mb-4">System Administration</h2>
            <div class="card mb-5 border-0 bg-light shadow-sm">
                <div class="card-header bg-dark text-white fw-bold">Add New Training Module / Gauge Study</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="text" name="title" class="form-control" placeholder="Title (Include 'Gauge' for Gauge Studies)" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <select name="department" class="form-select" required>
                                    <option value="all">-- All Department --</option>
                                    <option value="ACCOUNTING">ACCOUNTING</option>
                                    <option value="ASEPH BURN-IN">ASEPH BURN-IN</option>
                                    <option value="CML BURN-IN">CML BURN-IN</option>
                                    <option value="ENGINEERING">ENGINEERING</option>
                                    <option value="HR/ADMIN">HR/ADMIN</option>
                                    <option value="LOGISTIC">LOGISTIC</option>
                                    <option value="MACHINING">MACHINING</option>
                                    <option value="MARKETING/SALES">MARKETING/SALES</option>
                                    <option value="MIS">MIS</option>
                                    <option value="PLANNING">PLANNING</option>
                                    <option value="PRODUCTION">PRODUCTION</option>
                                    <option value="PURCHASING">PURCHASING</option>
                                    <option value="QA">QA</option>
                                    <option value="QA/TRAINING">QA/TRAINING</option>
                                    <option value="STORE">STORE</option>
                                    <option value="WAREHOUSE">WAREHOUSE</option>
                                </select>
                            </div>
                        </div>
                        <textarea name="description" class="form-control mb-3" placeholder="Short Description"></textarea>
                        <textarea name="content" class="form-control mb-3" placeholder="Full Content" rows="3"></textarea>
                        <button type="submit" name="add" class="btn btn-success px-5">Publish</button>
                    </form>
                </div>
            </div>

            <h4>Existing Normal Modules</h4>
            <table class="table table-hover align-middle">
                <thead class="table-dark"><tr><th>Title</th><th>Dept</th><th class="text-center">Action</th></tr></thead>
                <tbody>
                    <?php foreach ($modules as $m): if (stripos($m['title'], 'Gauge') !== false) continue; ?>
                    <tr>
                        <td><?= htmlspecialchars($m['title']) ?></td>
                        <td><?= htmlspecialchars($m['department']) ?></td>
                        <td class="text-center">
                            <a href="manage_exam.php?module_id=<?= $m['id'] ?>" class="btn btn-info btn-sm text-white">Manage Quiz</a>
                            <a href="?delete=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="gauge-admin">
        <div class="container admin-container shadow-sm border-start border-5 border-dark">
            <h2 class="mb-4">Gauge Study Management</h2>
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Study Title</th>
                        <th>Dept</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    foreach ($modules as $m): 
                        if (stripos($m['title'], 'Gauge') !== false): 
                            $count++;
                    ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($m['title']) ?></td>
                        <td><?= htmlspecialchars($m['department']) ?></td>
                        <td class="text-center">
                            <a href="view_gauge_report.php?id=<?= $m['id'] ?>" class="btn btn-success btn-sm fw-bold">View Report</a>
                            
                            <a href="gauge_control.php?id=<?= $m['id'] ?>" class="btn btn-dark btn-sm">Manage Answer Key</a>
                            <a href="?delete=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete Study?')">Delete</a>
                        </td>
                    </tr>
                    <?php endif; endforeach; ?>
                    
                    <?php if($count == 0): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No modules with "Gauge" in the title found. Create one in the "Manage Modules" tab first.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="users">
        <div class="container admin-container shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Employee Progress</h3>
                <input type="text" id="userSearchInput" class="form-control w-50" placeholder="Search by name or module...">
            </div>
            <table class="table table-hover align-middle">
                <thead class="table-dark"><tr><th>Username</th><th>Module</th><th>Score</th><th>Attempts</th><th>Action</th></tr></thead>
                <tbody id="attemptsTableBody">
                    <?php
                    $all_res = $conn->query("SELECT r.*, m.title FROM exam_results r JOIN modules m ON r.module_id = m.id ORDER BY r.username ASC");
                    while ($row = $all_res->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= $row['score'] ?> / <?= $row['total_questions'] ?></td>
                        <td><span class="badge <?= $row['attempts'] >= 3 ? 'bg-danger' : 'bg-info' ?>"><?= $row['attempts'] ?> / 3</span></td>
                        <td><a href="?reset_user=<?= urlencode($row['username']) ?>&mid=<?= $row['module_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Reset?')">Reset</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search Logic
document.getElementById('userSearchInput')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#attemptsTableBody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});
</script>
</body>
</html>