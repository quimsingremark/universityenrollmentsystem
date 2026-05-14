<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'Student';

$stmt = $conn->prepare("SELECT id, student_id, program, year_level, semester_level FROM students WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$student_id = $student['id'] ?? 0;
$year_level = $student['year_level'] ?? 1;
$semester_level = $student['semester_level'] ?? 1;
$program = $student['program'] ?? 'BSCS';
$school_id = $student['student_id'] ?? '';

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM enrollments WHERE student_id = ?");
$countStmt->bind_param("i", $student_id);
$countStmt->execute();
$total_enrolled = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;

$subjectStmt = $conn->prepare("\nSELECT COUNT(*) AS total\nFROM courses c\nWHERE c.year_level = ? AND c.semester_level = ?\nAND (c.program = ? OR c.program IS NULL OR c.program = '')
AND c.id NOT IN (SELECT course_id FROM enrollments WHERE student_id = ?)\n");
$subjectStmt->bind_param("iisi", $year_level, $semester_level, $program, $student_id);
$subjectStmt->execute();
$subjects_to_take = $subjectStmt->get_result()->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-container">
    <div class="topbar">
        <div>
            <h1>Student Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($fullname); ?> <?php echo $school_id ? '(' . htmlspecialchars($school_id) . ')' : ''; ?></p>
            <p><?php echo htmlspecialchars($program); ?> - Year <?php echo htmlspecialchars($year_level); ?>, Semester <?php echo htmlspecialchars($semester_level); ?></p>
        </div>
        <a class="logout-btn" href="../auth/logout.php">Logout</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $total_enrolled; ?></h3>
            <p>Enrolled Subjects</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $subjects_to_take; ?></h3>
            <p>Subjects To Take This Semester</p>
        </div>
    </div>

    <div class="menu-card">
        <a href="recommended_subjects.php">Subjects I Need To Take</a>
        <a href="enroll_subject.php">Enroll Current Subjects</a>
        <a href="next_semester_subjects.php">Next Semester Subjects</a>
        <a href="my_courses.php">My Enrolled Subjects</a>
    </div>

    <div class="content-card">
        <h2>Student Enrollment Guide</h2>
        <p>This portal only shows subjects based on your course/program, current year level, and semester. Higher year subjects are hidden until you reach that year/semester.</p>
    </div>
</div>
</body>
</html>
