<?php
include '../config/database.php';
include '../config/colleges.php';
ensureColleges($conn);
function ensureRegistrationStructure($conn) {
    // Keep existing data. Only add missing columns used by student pre-registration.
    if (function_exists('ensureColumnSafe')) {
        ensureColumnSafe($conn, 'users', 'login_id', "VARCHAR(30) NULL UNIQUE");
        ensureColumnSafe($conn, 'users', 'account_status', "ENUM('Pending','Confirmed','Rejected') DEFAULT 'Confirmed'");
        ensureColumnSafe($conn, 'students', 'student_id', "VARCHAR(50) NULL");
        ensureColumnSafe($conn, 'students', 'department_id', "INT NULL");
        ensureColumnSafe($conn, 'students', 'program', "VARCHAR(50) DEFAULT 'BSCS'");
        ensureColumnSafe($conn, 'students', 'year_level', "INT DEFAULT 1");
        ensureColumnSafe($conn, 'students', 'semester_level', "INT DEFAULT 1");
        ensureColumnSafe($conn, 'students', 'enrollment_date', "DATE NULL");
    }
}
ensureRegistrationStructure($conn);
$message = '';
$messageClass = 'message';

if (isset($_POST['submit'])) {
    $fullname = trim($_POST['fullname']);
    $login_id = trim($_POST['login_id']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department_id = intval($_POST['department_id']);
    $program = trim($_POST['program']);
    $year_level = intval($_POST['year_level']);
    $semester_level = intval($_POST['semester_level']);

    if ($fullname == '' || $login_id == '' || $email == '' || $program == '' || $department_id <= 0) {
        $message = 'Please complete all fields.';
        $messageClass = 'message status-danger';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE login_id=? OR email=? LIMIT 1");
        if (!$check) {
            $message = 'Registration setup error: ' . $conn->error;
            $messageClass = 'message status-danger';
        } else {
            $check->bind_param("ss", $login_id, $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = 'ID number or email already exists.';
                $messageClass = 'message status-danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO users(fullname, login_id, email, password, role, account_status) VALUES(?, ?, ?, ?, 'student', 'Pending')");
                if (!$stmt) {
                    $message = 'Registration setup error: ' . $conn->error;
                    $messageClass = 'message status-danger';
                } else {
                    $stmt->bind_param("ssss", $fullname, $login_id, $email, $password);
                    if ($stmt->execute()) {
                        $user_id = $conn->insert_id;
                        $student = $conn->prepare("INSERT INTO students(user_id, student_id, department_id, program, year_level, semester_level, enrollment_date) VALUES(?, ?, ?, ?, ?, ?, CURDATE())");
                        if ($student) {
                            $student->bind_param("isisii", $user_id, $login_id, $department_id, $program, $year_level, $semester_level);
                            $student->execute();
                        }
                        $message = 'Pre-registration sent. Please wait for the registrar to confirm your account before login.';
                    } else {
                        $message = 'Registration error: ' . $conn->error;
                        $messageClass = 'message status-danger';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Pre-Registration</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="form-container wide-form">
    <a class="back-link" href="login.php">&larr; Back to Login</a>
    <h1>Student Pre-Registration</h1>
    <p class="help-text">Students must pre-register first. The registrar will confirm your account before you can log in.</p>
    <?php if($message): ?><div class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="text" name="login_id" placeholder="Student ID e.g. 2026-0001-A" required>
        <input type="email" name="email" placeholder="Email" required>
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

        <label>Year Level</label>
        <input type="number" name="year_level" value="1" min="1" max="4" required>

        <label>Semester</label>
        <select name="semester_level" required>
            <option value="1">1st Semester</option>
            <option value="2">2nd Semester</option>
        </select>

        <button type="submit" name="submit">Submit Pre-Registration</button>
    </form>
</div>
<script>
const departmentSelect = document.getElementById('department_id');
const programSelect = document.getElementById('program');
function loadPrograms(departmentId) {
    programSelect.innerHTML = '<option value="">Loading programs...</option>';
    if (!departmentId) { programSelect.innerHTML = '<option value="">Select college first</option>'; return; }
    fetch('../registrar/get_programs.php?department_id=' + encodeURIComponent(departmentId))
        .then(response => response.json())
        .then(programs => {
            programSelect.innerHTML = '<option value="">Select Course / Program</option>';
            programs.forEach(program => {
                const option = document.createElement('option');
                option.value = program.program_code;
                option.textContent = program.program_code + ' - ' + program.program_name;
                programSelect.appendChild(option);
            });
        });
}
departmentSelect.addEventListener('change', function () { loadPrograms(this.value); });
</script>
</body>
</html>
