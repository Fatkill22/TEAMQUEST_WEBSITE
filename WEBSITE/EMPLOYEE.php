<?php
session_start();
include "config.php";


if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

$dept = $_SESSION['department'];


$modules = [];
$stmt = $conn->prepare("SELECT * FROM modules WHERE department = ? OR department = 'all' ORDER BY id DESC");
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Training - TeamQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Restoring your original branding styles */
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

        .module-card {
            border: 2px solid rgb(0, 0, 0);
            border-radius: 10px;
            overflow: hidden;
            text-align: center;
            background-color: #f9f9f9;
            transition: transform 0.3s;
            height: 100%; 
        }

        .module-card:hover { 
            transform: scale(1.05); 
        }

        .module-card img { 
            width: 100%; 
            height: 250px; 
            object-fit: cover; 
        }

        .module-description { 
            padding: 15px; 
        }

        .nav-tabs .nav-link { color: #000; font-weight: bold; }
        .nav-tabs .nav-link.active { background-color: #e9ecef; }
    </style>
</head>

<body>

<div class="logo text-white">
    <img src="Images/Logo.png" alt="Company Logo">
</div>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#home">Home</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#modules">Modules</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#performance">Performance Report</button>
    </li>
    <li class="nav-item ms-auto">
        <a href="INDEX.html" class="nav-link text-danger">Logout (<?php echo $_SESSION['username']; ?>)</a>
    </li>
</ul>

<div class="tab-content">

    <div class="tab-pane fade show active" id="home">
        <div class="Homepage-Tab">
            <div class="Homepage-button-tab">
                <button type="button" class="btn btn-primary shadow-lg" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('button[data-bs-target=\'#modules\']')).show()">
                    Start Online Training
                </button>
            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="modules">
        <div class="container">
            <h2 class="mb-4">Training Modules: <?php echo $dept; ?></h2>
            <div class="row">

                <?php if (!empty($modules)) { ?>
                    <?php foreach ($modules as $module) { ?>

                        <div class="col-md-6 mb-4">
                            <div class="module-card shadow-sm"
                                 style="cursor:pointer;"
                                 onclick="window.location.href='view_module.php?id=<?php echo $module['id']; ?>'">

                                <?php if (!empty($module['image'])) { ?>
                                    <img src="Images/<?php echo $module['image']; ?>"
                                         alt="<?php echo $module['title']; ?>">
                                <?php } else { ?>
                                    <div class="bg-secondary py-5 text-white">No Image Available</div>
                                <?php } ?>

                                <div class="module-description">
                                    <h4 class="fw-bold"><?php echo $module['title']; ?></h4>
                                    <p class="text-muted"><?php echo $module['description']; ?></p>
                                    <span class="btn btn-sm btn-dark">Open Module</span>
                                </div>
                            </div>
                        </div>

                    <?php } ?>
                <?php } else { ?>
                    <div class="col-12 text-center mt-5">
                        <p class="text-muted fs-4">No modules available for your department at this time.</p>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>

    <div class="tab-pane fade p-4" id="performance">
        <div class="container text-center py-5">
            <h3>Performance Report</h3>
            <p class="text-muted">Tracking and reporting section coming soon.</p>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>