<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, department_id, program, year_level, semester_level FROM students WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$student_id = $student['id'] ?? 0;
$department_id = $student['department_id'] ?? 0;
$current_year = $student['year_level'] ?? 1;
$current_sem = $student['semester_level'] ?? 1;
$program = $student['program'] ?? 'BSCS';

$next_year = $current_year;
$next_sem = 2;
if ($current_sem == 2) {
    $next_year = $current_year + 1;
    $next_sem = 1;
}

$query = $conn->prepare("\nSELECT c.id, c.course_code, c.title, c.credits, c.year_level, c.semester_level,\n       pre.course_code AS pre_code, pre.title AS pre_title,\n       CASE\n           WHEN c.prerequisite_course_id IS NULL THEN 'Available next semester'\n           WHEN EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = ? AND e.course_id = c.prerequisite_course_id AND e.remarks = 'PASSED') THEN 'Available next semester'\n           ELSE 'Need to pass prerequisite first'\n       END AS eligibility\nFROM courses c\nLEFT JOIN courses pre ON c.prerequisite_course_id = pre.id\nWHERE c.year_level = ?\nAND c.semester_level = ?\nAND (c.department_id = ? OR ? = 0)\nAND (c.program = ? OR c.program IS NULL OR c.program = '')
AND c.id NOT IN (SELECT course_id FROM enrollments WHERE student_id = ?)\nORDER BY c.course_code\n");
$query->bind_param("iiiiisi", $student_id, $next_year, $next_sem, $department_id, $department_id, $program, $student_id);
$query->execute();
$result = $query->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Next Semester Subjects</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-container">
    <div class="topbar">
        <div>
            <h1>Next Semester Subjects</h1>
            <p>Recommended <?php echo htmlspecialchars($program); ?> subjects for Year <?php echo htmlspecialchars($next_year); ?> - Semester <?php echo htmlspecialchars($next_sem); ?></p>
        </div>
        <a class="logout-btn" href="../auth/logout.php">Logout</a>
    </div>
    <div class="menu-card">
        <a href="dashboard.php">Dashboard</a>
        <a href="recommended_subjects.php">Subjects I Need To Take</a>
        <a href="enroll_subject.php">Enroll Current Subjects</a>
        <a href="my_courses.php">My Enrolled Subjects</a>
    </div>
    <div class="content-card">
        <h2>Subjects for Next Semester Only</h2>
        <table>
            <tr>
                <th>Code</th>
                <th>Subject</th>
                <th>Units</th>
                <th>Prerequisite</th>
                <th>Status</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['credits']); ?></td>
                        <td><?php echo $row['pre_code'] ? htmlspecialchars($row['pre_code'].' - '.$row['pre_title']) : 'None'; ?></td>
                        <td><span class="status <?php echo strpos($row['eligibility'], 'Need') !== false ? 'status-danger' : ''; ?>"><?php echo htmlspecialchars($row['eligibility']); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="empty">No next semester subjects found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
