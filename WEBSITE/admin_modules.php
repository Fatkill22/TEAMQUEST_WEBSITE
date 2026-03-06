<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') {
    header("Location: INDEX.php");
    exit();
}

/* ADD MODULE */
if (isset($_POST['add'])) {

    $title = $_POST['title'];
    $description = $_POST['description'];
    $department = $_POST['department'];

    $imageName = "";
    $videoName = "";

    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "Images/" . $imageName);
    }

    if (!empty($_FILES['video']['name'])) {
        $videoName = time() . "_" . $_FILES['video']['name'];
        move_uploaded_file($_FILES['video']['tmp_name'], "Videos/" . $videoName);
    }

    $stmt = $conn->prepare("INSERT INTO modules (title, description, image, video, department) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $description, $imageName, $videoName, $department);
    $stmt->execute();
}

/* DELETE MODULE */
if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $get = $conn->prepare("SELECT image, video FROM modules WHERE id=?");
    $get->bind_param("i", $id);
    $get->execute();
    $row = $get->get_result()->fetch_assoc();

    if ($row) {
        if (!empty($row['image']) && file_exists("Images/" . $row['image'])) {
            unlink("Images/" . $row['image']);
        }
        if (!empty($row['video']) && file_exists("Videos/" . $row['video'])) {
            unlink("Videos/" . $row['video']);
        }
    }

    $stmt = $conn->prepare("DELETE FROM modules WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

/* UPDATE MODULE */
if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $department = $_POST['department'];

    $get = $conn->prepare("SELECT image, video FROM modules WHERE id=?");
    $get->bind_param("i", $id);
    $get->execute();
    $current = $get->get_result()->fetch_assoc();

    $imageName = $current['image'];
    $videoName = $current['video'];

    if (!empty($_FILES['image']['name'])) {
        if (!empty($imageName) && file_exists("Images/" . $imageName)) {
            unlink("Images/" . $imageName);
        }
        $imageName = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "Images/" . $imageName);
    }

    if (!empty($_FILES['video']['name'])) {
        if (!empty($videoName) && file_exists("Videos/" . $videoName)) {
            unlink("Videos/" . $videoName);
        }
        $videoName = time() . "_" . $_FILES['video']['name'];
        move_uploaded_file($_FILES['video']['tmp_name'], "Videos/" . $videoName);
    }

    $stmt = $conn->prepare("UPDATE modules SET title=?, description=?, image=?, video=?, department=? WHERE id=?");
    $stmt->bind_param("sssssi", $title, $description, $imageName, $videoName, $department, $id);
    $stmt->execute();
}

$result = $conn->query("SELECT * FROM modules ORDER BY id DESC");
$modules = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin - Manage Modules</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">

<div class="container">
<h2>Admin Panel - Manage Modules</h2>

<div class="card mb-4">
<div class="card-header">Add Module</div>
<div class="card-body">
<form method="POST" enctype="multipart/form-data">
<input type="text" name="title" class="form-control mb-2" placeholder="Title" required>
<textarea name="description" class="form-control mb-2" placeholder="Description" required></textarea>

<select name="department" class="form-control mb-2" required>
<option value="">Select Department</option>
<option value="A">A</option>
<option value="B">B</option>
<option value="C">C</option>
</select>

<input type="file" name="image" class="form-control mb-2">
<input type="file" name="video" class="form-control mb-2">

<button type="submit" name="add" class="btn btn-primary">Add Module</button>
</form>
</div>
</div>

<h4>Existing Modules</h4>

<table class="table table-bordered">
<tr>
<th>ID</th>
<th>Title</th>
<th>Department</th>
<th>Action</th>s
</tr>

<?php foreach ($modules as $module) { ?>
<tr>
<td><?php echo $module['id']; ?></td>
<td><?php echo $module['title']; ?></td>
<td><?php echo $module['department']; ?></td>
<td>
<a href="?delete=<?php echo $module['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
</td>
</tr>
<?php } ?>

</table>

</div>
</body>
</html>