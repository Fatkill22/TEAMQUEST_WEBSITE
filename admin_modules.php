<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.php"); exit();
}

// Handle Adding Modules
if (isset($_POST['add'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $content = $_POST['content'];
    $department = $_POST['department'];
    $imageName = !empty($_FILES['image']['name']) ? time()."_".$_FILES['image']['name'] : "";
    $videoName = !empty($_FILES['video']['name']) ? time()."_".$_FILES['video']['name'] : "";

    if($imageName) move_uploaded_file($_FILES['image']['tmp_name'], "Images/".$imageName);
    if($videoName) move_uploaded_file($_FILES['video']['tmp_name'], "Videos/".$videoName);

    // FIXED: Added 'content' to the SQL
    $stmt = $conn->prepare("INSERT INTO modules (title, description, content, image, video, department) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $title, $description, $content, $imageName, $videoName, $department);
    $stmt->execute();
}

// Handle Deleting Modules
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM modules WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin_modules.php");
    exit();
}

$modules = $conn->query("SELECT * FROM modules ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Modules</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Panel</h2>
        <a href="INDEX.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white fw-bold">Add New Training Module</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" name="title" class="form-control" placeholder="Module Title" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <select name="department" class="form-select" required>
                            <option value="all">All Departments</option>
                            <option value="ACCOUNTING">ACCOUNTING</option>
                            <option value="ASEPH BURN-IN">ASEPH BURN-IN</option>
                            <option value="CML BURN-IN">CML BURN-IN</option>
                            <option value="ENGINEERING">ENGINEERING</option>
                            <option value="HR/ADMIN">HR/ADMIN</option>
                            <option value="LOGISTICS">LOGISTICS</option>
                            <option value="MACHINING">MACHINING</option>
                            <option value="MARKETING">MARKETING/SALES</option>
                            <option value="MIS">MIS</option>
                            <option value="PLANNING">PLANNING</option>
                            <option value="PRODUCTION">PRODUCTION</option>
                            <option value="PURCHASING">PURCHASING</option>
                            <option value="QA">QA</option>
                            <option value="QA/TRAINING">QA/TRAINING</option>
                            <option value="STOR">STORE</option>
                            <option value="WAREHOUSE">WAREHOUSE</option>
                        </select>
                    </div>
                </div>
                <textarea name="description" class="form-control mb-2" placeholder="Short Summary (shown on cards)" required></textarea>
                <textarea name="content" class="form-control mb-2" placeholder="Full Detailed Information (The Template Content)" rows="6"></textarea>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Thumbnail Image</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label">Training Video</label>
                        <input type="file" name="video" class="form-control">
                    </div>
                </div>
                <button type="submit" name="add" class="btn btn-success w-100">Publish Module</button>
            </form>
        </div>
    </div>

    <h4>Existing Modules</h4>
    <table class="table table-hover bg-white shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>Title</th>
                <th>Dept</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($modules as $m) { ?>
            <tr>
                <td><?= htmlspecialchars($m['title']) ?></td>
                <td><?= htmlspecialchars($m['department']) ?></td>
                <td>
                    <a href="edit_module.php?id=<?= $m['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="?delete=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this module?')">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>