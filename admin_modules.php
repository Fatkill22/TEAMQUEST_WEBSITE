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

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel - TeamQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Shared Branding Styles */
        .logo img { height: 70px; width: auto; }
        .logo { background-color: blue; padding: 10px; }

        .Homepage-Tab {
            background-image: url("Images/TeamQuest.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 800px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .Homepage-button-tab .btn {
            font-size: 28px;
            padding: 20px 60px;
        }

        .nav-tabs .nav-link { color: #000; font-weight: bold; }
        .nav-tabs .nav-link.active { background-color: #e9ecef; }
        
        /* Admin Specific Styling */
        .admin-container { background-color: white; border-radius: 10px; padding: 30px; margin-top: 20px; }
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
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#admin">Admin Panel</button>
    </li>
    <li class="nav-item ms-auto">
        <a href="INDEX.html" class="nav-link text-danger">Logout (Admin: <?php echo $_SESSION['username']; ?>)</a>
    </li>
</ul>

<div class="tab-content">

    <div class="tab-pane fade show active" id="home">
        <div class="Homepage-Tab">
            <div class="Homepage-button-tab">
                <button type="button" class="btn btn-primary shadow-lg" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('button[data-bs-target=\'#admin\']')).show()">
                    Manage Training System
                </button>
            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="admin">
        <div class="container admin-container shadow-sm">
            <h2 class="mb-4">System Administration</h2>
            
            <div class="card mb-5 border-0 bg-light">
                <div class="card-header bg-dark text-white fw-bold">Add New Training Module</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Module Title</label>
                                <input type="text" name="title" class="form-control" placeholder="Enter Title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Assign Department</label>
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
                        <div class="mb-3">
                            <label class="form-label fw-bold">Short Description</label>
                            <textarea name="description" class="form-control" placeholder="Brief summary for the card view" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Main Content</label>
                            <textarea name="content" class="form-control" placeholder="Detailed module information..." rows="5"></textarea>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Thumbnail Image</label>
                                <input type="file" name="image" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Training Video</label>
                                <input type="file" name="video" class="form-control">
                            </div>
                        </div>
                        <button type="submit" name="add" class="btn btn-success px-5">Publish Module</button>
                    </form>
                </div>
            </div>

            <h4 class="mb-3">Manage Existing Content</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Department</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $m) { ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($m['title']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['department']) ?></span></td>
                            <td class="text-center">
                                <a href="manage_exam.php?module_id=<?= $m['id'] ?>" class="btn btn-info btn-sm text-white shadow-sm">Manage Exam</a>
                                <a href="edit_module.php?id=<?= $m['id'] ?>" class="btn btn-warning btn-sm shadow-sm">Edit</a>
                                <a href="?delete=<?= $m['id'] ?>" class="btn btn-danger btn-sm shadow-sm" onclick="return confirm('Delete this module?')">Delete</a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>