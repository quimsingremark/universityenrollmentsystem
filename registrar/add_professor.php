<?php
session_start();
include '../config/database.php';
include '../config/colleges.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../auth/login.php");
    exit();
}

ensureColleges($conn);
$message = "";

if(isset($_POST['submit'])) {

    $fullname = trim($_POST['fullname']);
    $login_id = trim($_POST['login_id']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $specialization = trim($_POST['specialization']);
    $department_id = intval($_POST['department_id']);

    $stmt = $conn->prepare("INSERT INTO users(fullname, login_id, email, password, role) VALUES(?, ?, ?, ?, 'professor')");
    $stmt->bind_param("ssss", $fullname, $login_id, $email, $password);

    if($stmt->execute()) {
        $user_id = $conn->insert_id;
        $professor_stmt = $conn->prepare("INSERT INTO professors(user_id, employee_id, specialization, department_id) VALUES(?, ?, ?, ?)");
        $professor_stmt->bind_param("issi", $user_id, $login_id, $specialization, $department_id);
        $professor_stmt->execute();
        $message = "Professor added successfully. Login ID: " . htmlspecialchars($login_id);
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Professor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="form-container">
    <h1>Add Professor</h1>
    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <?php if($message != "") echo "<div class='message'>$message</div>"; ?>

    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="text" name="login_id" placeholder="Professor ID e.g. PROF-0001" required>
        <input type="email" name="email" placeholder="Email for records" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="specialization" placeholder="Specialization" required>

        <label>College</label>
        <select name="department_id" required>
            <option value="">Select College</option>
            <?php collegeOptions($conn); ?>
        </select>

        <button type="submit" name="submit">Add Professor</button>
    </form>
</div>
</body>
</html>
