<?php
session_start();
include "config.php";

// 1. Security Check: Only allow logged-in admins
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

// 3. Handle Resetting Attempts (Deletes result record so user can restart)
if (isset($_GET['reset_user']) && isset($_GET['mid'])) {
    $user_to_reset = $_GET['reset_user'];
    $mid           = (int)$_GET['mid'];

    $stmt = $conn->prepare("DELETE FROM exam_results WHERE username = ? AND module_id = ?");
    $stmt->bind_param("si", $user_to_reset, $mid);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_modules.php#users"); // Redirect back to users tab
    exit();
}

// 4. Handle Deleting Modules
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM modules WHERE id=$id");
    header("Location: admin_modules.php");
    exit();
}

// Fetch existing modules for display
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
        .Homepage-Tab {
            background-image: url("Images/TeamQuest.jpg");
            background-size: cover;
            background-position: center;
            min-height: 800px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .nav-tabs .nav-link { color: #000; font-weight: bold; }
        .nav-tabs .nav-link.active { background-color: #e9ecef; }
        .admin-container { background-color: white; border-radius: 10px; padding: 30px; margin-top: 20px; }
        #userSearchInput { border: 2px solid #0d6efd; border-radius: 20px; }
    </style>
</head>
<body class="bg-light">

<div class="logo text-white">
    <img src="Images/Logo.png" alt="Company Logo">
</div>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#home">Home</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#admin">Manage Modules</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#users">User Attempts</button>
    </li>
    <li class="nav-item ms-auto">
        <a href="INDEX.html" class="nav-link text-danger">Logout (Admin: <?= $_SESSION['username'] ?>)</a>
    </li>
</ul>

<div class="tab-content">

    <div class="tab-pane fade show active" id="home">
        <div class="Homepage-Tab">
            <div class="text-center">
                <button type="button" class="btn btn-primary btn-lg shadow-lg" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('button[data-bs-target=\'#admin\']')).show()">
                    Manage Training System
                </button>
            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="admin">
        <div class="container admin-container shadow-sm">
            <h2 class="mb-4">System Administration</h2>
            
            <div class="card mb-5 border-0 bg-light shadow-sm">
                <div class="card-header bg-dark text-white fw-bold">Add New Training Module</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="text" name="title" class="form-control" placeholder="Module Title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <select name="department" class="form-select" required>
                                    <option value="all">All Departments</option>
                                    <option value="PRODUCTION">PRODUCTION</option>
                                    <option value="ENGINEERING">ENGINEERING</option>
                                </select>
                            </div>
                        </div>
                        <textarea name="description" class="form-control mb-3" placeholder="Short Description" required></textarea>
                        <textarea name="content" class="form-control mb-3" placeholder="Full Content" rows="5"></textarea>
                        <div class="row mb-3">
                            <div class="col-md-6"><label class="small">Module Image</label><input type="file" name="image" class="form-control"></div>
                            <div class="col-md-6"><label class="small">Module Video</label><input type="file" name="video" class="form-control"></div>
                        </div>
                        <button type="submit" name="add" class="btn btn-success px-5">Publish Module</button>
                    </form>
                </div>
            </div>

            <h4>Existing Modules</h4>
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Dept</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['title']) ?></td>
                        <td><?= htmlspecialchars($m['department']) ?></td>
                        <td class="text-center">
                            <a href="manage_exam.php?module_id=<?= $m['id'] ?>" class="btn btn-info btn-sm text-white">Manage Exam</a>
                            <a href="edit_module.php?id=<?= $m['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?delete=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this module?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="users">
        <div class="container admin-container shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Employee Exam Progress</h3>
                <div class="w-50">
                    <input type="text" id="userSearchInput" class="form-control shadow-sm" placeholder="Search by username or module title...">
                </div>
            </div>

            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Username</th>
                        <th>Module</th>
                        <th>Score</th>
                        <th>Attempts</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="attemptsTableBody">
                    <?php
                    $all_res = $conn->query("SELECT r.*, m.title FROM exam_results r JOIN modules m ON r.module_id = m.id ORDER BY r.username ASC");
                    if ($all_res->num_rows > 0) {
                        while ($row = $all_res->fetch_assoc()) {
                            $is_limited = ($row['attempts'] >= 3);
                            echo "<tr>
                                <td class='target-user fw-bold'>".htmlspecialchars($row['username'])."</td>
                                <td class='target-module'>".htmlspecialchars($row['title'])."</td>
                                <td><span class='badge bg-light text-dark border'>{$row['score']} / {$row['total_questions']}</span></td>
                                <td><span class='badge ".($is_limited ? 'bg-danger' : 'bg-info')."'>{$row['attempts']} / 3</span></td>
                                <td class='text-center'>
                                    <a href='?reset_user=".urlencode($row['username'])."&mid={$row['module_id']}' 
                                       class='btn btn-outline-danger btn-sm' 
                                       onclick='return confirm(\"Reset attempts for ".htmlspecialchars($row['username'])."?\")'>
                                       Reset Attempts
                                    </a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-4'>No exam results found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('userSearchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#attemptsTableBody tr');

    rows.forEach(row => {
        let username = row.querySelector('.target-user').textContent.toLowerCase();
        let moduleTitle = row.querySelector('.target-module').textContent.toLowerCase();
        if (username.includes(filter) || moduleTitle.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>