<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.php"); exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_modules.php"); exit();
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$module = $stmt->get_result()->fetch_assoc();

if (!$module) {
    die("Module not found.");
}

if (isset($_POST['update'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $content = $_POST['content'];
    $department = $_POST['department'];
    
    // Check for new image
    if (!empty($_FILES['image']['name'])) {
        $imageName = time()."_".$_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "Images/".$imageName);
    } else {
        $imageName = $module['image'];
    }

    // Check for new video
    if (!empty($_FILES['video']['name'])) {
        $videoName = time()."_".$_FILES['video']['name'];
        move_uploaded_file($_FILES['video']['tmp_name'], "Videos/".$videoName);
    } else {
        $videoName = $module['video'];
    }

    $updateStmt = $conn->prepare("UPDATE modules SET title=?, description=?, content=?, image=?, video=?, department=? WHERE id=?");
    $updateStmt->bind_param("ssssssi", $title, $description, $content, $imageName, $videoName, $department, $id);
    
    if ($updateStmt->execute()) {
        echo "<script>alert('Module Updated Successfully'); window.location.href='admin_modules.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Training Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-warning fw-bold">Edit Training Module</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">Module Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($module['title']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-bold">Assigned Department</label>
                        <select name="department" class="form-select" required>
                            <?php 
                            $depts = ["all", "ACCOUNTING", "ASEPH BURN-IN", "CML BURN-IN", "ENGINEERING", "HR/ADMIN", "LOGISTICS", "MACHINING", "MARKETING", "MIS", "PLANNING", "PRODUCTION", "PURCHASING", "QA", "QA/TRAINING", "STOR", "WAREHOUSE"];
                            foreach($depts as $d) {
                                $selected = ($module['department'] == $d) ? "selected" : "";
                                echo "<option value='$d' $selected>$d</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-2">
                    <label class="form-label fw-bold">Short Summary</label>
                    <textarea name="description" class="form-control" required><?= htmlspecialchars($module['description']) ?></textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold">Full Detailed Information</label>
                    <textarea name="content" class="form-control" rows="6"><?= htmlspecialchars($module['content'] ?? '') ?></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Update Thumbnail Image</label>
                        <input type="file" name="image" class="form-control">
                        <small class="text-muted">Current: <?= $module['image'] ?: 'None' ?></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Update Training Video</label>
                        <input type="file" name="video" class="form-control">
                        <small class="text-muted">Current: <?= $module['video'] ?: 'None' ?></small>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="update" class="btn btn-primary w-100">Save Changes</button>
                    <a href="admin_modules.php" class="btn btn-secondary w-100">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>