<?php
session_start();
include "config.php";

// Make sure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: INDEX.php");
    exit();
}

// Fetch modules
$modules = [];
$sql = "SELECT * FROM modules ORDER BY id DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row;
    }
}
$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Online Training</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
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
    padding: 10px; 
}
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
</ul>

<div class="tab-content">

    <!-- HOME TAB -->
    <div class="tab-pane fade show active" id="home">
        <div class="Homepage-Tab">
            <div class="Homepage-button-tab">
                <button type="button" class="btn btn-primary">
                    Start Online Training
                </button>
            </div>
        </div>
    </div>

    <!-- MODULES TAB -->
    <div class="tab-pane fade p-4" id="modules">
        <div class="container">
            <div class="row">

                <?php if (!empty($modules)) { ?>
                    
                    <?php foreach ($modules as $module) { ?>

                        <div class="col-md-6 mb-4">

                            <div class="module-card"
                                 style="cursor:pointer;"
                                 onclick="window.location.href='<?php 
                                    echo !empty($module['video']) 
                                    ? "Videos/" . $module['video'] 
                                    : "#"; ?>'">

                                <!-- MODULE IMAGE -->
                                <?php if (!empty($module['image'])) { ?>
                                    <img src="Images/<?php echo $module['image']; ?>"
                                         alt="<?php echo $module['title']; ?>"
                                         class="img-fluid">
                                <?php } ?>

                                <div class="module-description">
                                    <h4><?php echo $module['title']; ?></h4>
                                    <p><?php echo $module['description']; ?></p>
                                </div>

                            </div>

                        </div>

                    <?php } ?>

                <?php } else { ?>

                    <div class="col-12">
                        <p class="text-center text-muted">
                            No modules available at the moment.
                        </p>
                    </div>

                <?php } ?>

            </div>
        </div>
    </div>

    <!-- PERFORMANCE TAB -->
    <div class="tab-pane fade p-4" id="performance">
        <p class="text-center">
            Performance Report section coming soon.
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>