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
    $department_id = intval($_POST['department_id']);
    $year_level = intval($_POST['year_level'] ?? 1);
    $semester_level = intval($_POST['semester_level'] ?? 1);
    $program = trim($_POST['program'] ?? 'BSCS');

    $stmt = $conn->prepare("INSERT INTO users(fullname, login_id, email, password, role, account_status) VALUES(?, ?, ?, ?, 'student', 'Confirmed')");
    $stmt->bind_param("ssss", $fullname, $login_id, $email, $password);

    if($stmt->execute()) {

        $user_id = $conn->insert_id;

        $student_stmt = $conn->prepare("INSERT INTO students(user_id, student_id, department_id, program, year_level, semester_level, enrollment_date) VALUES(?, ?, ?, ?, ?, ?, NOW())");
        $student_stmt->bind_param("isisii", $user_id, $login_id, $department_id, $program, $year_level, $semester_level);
        $student_stmt->execute();

        $message = "Student added successfully. Login ID: " . htmlspecialchars($login_id);
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="form-container">
    <h1>Add Confirmed Student</h1>
    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <?php if($message != "") echo "<div class='message'>$message</div>"; ?>

    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="text" name="login_id" placeholder="Student ID e.g. 2026-0001-A" required>
        <input type="email" name="email" placeholder="Email for records" required>
        <input type="password" name="password" placeholder="Password" required>

        <label>College / Department</label>
        <select name="department_id" id="department_id" required>
            <option value="">Select College</option>
            <?php collegeOptions($conn); ?>
        </select>

        <label>Course / Program</label>
        <select name="program" id="program" required>
            <option value="">Select college first</option>
        </select>

        <input type="number" name="year_level" placeholder="Year Level" value="1" min="1" max="4" required>
        <select name="semester_level" required>
            <option value="1">1st Semester</option>
            <option value="2">2nd Semester</option>
        </select>

        <button type="submit" name="submit">Add Student</button>
    </form>
</div>

<script>
const departmentSelect = document.getElementById('department_id');
const programSelect = document.getElementById('program');

function loadPrograms(departmentId) {
    programSelect.innerHTML = '<option value="">Loading programs...</option>';

    if (!departmentId) {
        programSelect.innerHTML = '<option value="">Select college first</option>';
        return;
    }

    fetch('get_programs.php?department_id=' + encodeURIComponent(departmentId))
        .then(response => response.json())
        .then(programs => {
            programSelect.innerHTML = '<option value="">Select Course / Program</option>';

            if (programs.length === 0) {
                programSelect.innerHTML = '<option value="">No programs found</option>';
                return;
            }

            programs.forEach(program => {
                const option = document.createElement('option');
                option.value = program.program_code;
                option.textContent = program.program_code + ' - ' + program.program_name;
                programSelect.appendChild(option);
            });
        })
        .catch(() => {
            programSelect.innerHTML = '<option value="">Error loading programs</option>';
        });
}

departmentSelect.addEventListener('change', function () {
    loadPrograms(this.value);
});
</script>
</body>
</html>
