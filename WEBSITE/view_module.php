<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: EMPLOYEE.php");
    exit();
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$module = $stmt->get_result()->fetch_assoc();

if (!$module) {
    die("Training module not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $module['title']; ?> - Training</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .content-container { background: white; border-radius: 15px; margin-top: -80px; padding: 40px; position: relative; z-index: 10; }
        .module-header { background: #0d6efd; background: linear-gradient(135deg, #0d6efd 0%, #0044cc 100%); color: white; padding: 100px 0 140px 0; text-align: center; }
        .video-box { background: #000; border-radius: 12px; overflow: hidden; margin: 30px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    </style>
</head>
<body class="bg-light">

    <div class="module-header">
        <div class="container">
            <h1 class="display-4 fw-bold"><?php echo $module['title']; ?></h1>
            <p class="opacity-75">Department: <?php echo $module['department']; ?></p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 content-container shadow-sm">
                
                <a href="EMPLOYEE.php" class="btn btn-outline-secondary mb-4">&larr; Back to Dashboard</a>

                <div class="row">
                    <div class="col-12">
                        <h3 class="text-primary mb-3 text-uppercase fw-bold">Introduction</h3>
                        <p class="fs-5 text-dark"><?php echo nl2br($module['description']); ?></p>
                        <hr class="my-4">
                    </div>
                    
                    <div class="col-12 mt-2">
                        <h4 class="fw-bold mb-3">Module Information</h4>
                        <div class="lh-lg" style="font-size: 1.1rem; color: #333;">
                            <?php echo nl2br($module['content']); ?>
                        </div>
                    </div>

                    <?php if (!empty($module['video'])): ?>
                    <div class="col-12">
                        <div class="video-box">
                            <video width="100%" controls controlsList="nodownload">
                                <source src="Videos/<?php echo $module['video']; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="text-center mt-5">
                    <button class="btn btn-success btn-lg px-5 shadow" onclick="alert('Module Completed!')">Finish Module</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 text-muted">
        &copy; 2026 TeamQuest E-Learning
    </footer>

</body>
</html>