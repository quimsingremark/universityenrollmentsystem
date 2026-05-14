<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['id'] ?? 0;

$query = $conn->prepare("
SELECT 
    c.course_code, 
    c.title, 
    c.credits, 
    e.semester, 
    e.grade, 
    e.remarks, 
    e.status,
    pre.course_code AS pre_code, 
    pre.title AS pre_title,
    cs.section AS section_name,
    u.fullname AS professor_name
FROM enrollments e
INNER JOIN courses c ON e.course_id = c.id
LEFT JOIN courses pre ON c.prerequisite_course_id = pre.id
LEFT JOIN course_sections cs ON e.section_id = cs.id
LEFT JOIN professors p ON cs.professor_id = p.id
LEFT JOIN users u ON p.user_id = u.id
WHERE e.student_id = ?
ORDER BY e.id DESC
");

$query->bind_param("i", $student_id);
$query->execute();
$result = $query->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Enrolled Subjects</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-container">
    <div class="topbar">
        <div>
            <h1>My Enrolled Subjects</h1>
            <p>View your subjects, grades, and prerequisites.</p>
        </div>
        <a class="logout-btn" href="../auth/logout.php">Logout</a>
    </div>

    <div class="menu-card">
        <a href="dashboard.php">Dashboard</a>
        <a href="enroll_subject.php">Enroll Subject</a>
        <a href="next_semester_subjects.php">Next Semester Subjects</a>
    </div>

    <div class="content-card">
        <h2>Subject Records</h2>

        <table>
            <tr>
                <th>Code</th>
                <th>Subject</th>
                <th>Units</th>
                <th>Section</th>
                <th>Professor</th>
                <th>Semester</th>
                <th>Prerequisite</th>
                <th>Grade</th>
                <th>Remarks</th>
            </tr>

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['credits']); ?></td>
                        <td><?php echo !empty($row['section_name']) ? htmlspecialchars($row['section_name']) : 'Pending section'; ?></td>
                        <td><?php echo !empty($row['professor_name']) ? htmlspecialchars($row['professor_name']) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($row['semester']); ?></td>
                        <td>
                            <?php echo !empty($row['pre_code']) 
                                ? htmlspecialchars($row['pre_code'] . ' - ' . $row['pre_title']) 
                                : 'None'; 
                            ?>
                        </td>
                        <td><?php echo !empty($row['grade']) ? htmlspecialchars($row['grade']) : 'N/A'; ?></td>
                        <td>
                            <span class="status">
                                <?php echo !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : htmlspecialchars($row['status'] ?? 'ONGOING'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="empty">No subjects enrolled yet.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
