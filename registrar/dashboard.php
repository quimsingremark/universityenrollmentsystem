<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../auth/login.php");
    exit();
}
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-container">
    <div class="topbar">
        <div>
            <h1>Registrar Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
        </div>
        <a class="logout-btn" href="../auth/logout.php">Logout</a>
    </div>

    <div class="menu-card">
        <a href="add_student.php">Add Student</a>
        <a href="add_professor.php">Add Professor</a>
        <a href="add_course.php">Add Subject</a>
        <a href="assign_subject.php">Assign Subjects to Professors</a>
        <a href="confirm_students.php">Confirm Students</a>
    </div>

    <?php if($msg): ?><div class="message"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>


    <div class="content-card">
        <h2>Pending Student Pre-Registrations</h2>
        <p class="help-text">Students can pre-register, but they cannot log in until the registrar confirms their account.</p>
        <table>
            <tr><th>ID Number</th><th>Name</th><th>College</th><th>Program</th><th>Year/Sem</th><th>Action</th></tr>
            <?php
            $pendingStudents = $conn->query("SELECT u.id AS user_id, u.fullname, u.login_id, s.program, s.year_level, s.semester_level, d.department_name FROM users u INNER JOIN students s ON u.id=s.user_id LEFT JOIN departments d ON s.department_id=d.id WHERE u.role='student' AND u.account_status='Pending' ORDER BY u.id DESC");
            if ($pendingStudents && $pendingStudents->num_rows > 0):
                while($ps = $pendingStudents->fetch_assoc()):
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($ps['login_id']); ?></td>
                    <td><?php echo htmlspecialchars($ps['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($ps['department_name']); ?></td>
                    <td><?php echo htmlspecialchars($ps['program']); ?></td>
                    <td>Year <?php echo htmlspecialchars($ps['year_level']); ?> - Sem <?php echo htmlspecialchars($ps['semester_level']); ?></td>
                    <td><a class="small-btn approve-btn" href="confirm_students.php?action=confirm&id=<?php echo $ps['user_id']; ?>" onclick="return confirm('Confirm this student account?');">Confirm</a> <a class="small-btn reject-btn" href="confirm_students.php?action=reject&id=<?php echo $ps['user_id']; ?>" onclick="return confirm('Reject this student account?');">Reject</a></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="6" class="empty">No pending student pre-registrations.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="content-card">
        <h2>Pending Subject Pre-Registration Confirmations</h2>
        <p class="help-text">Approve only if the student is eligible for the subject. The system also checks college, program/course, year, semester, and prerequisites.</p>
        <table>
            <tr>
                <th>Student</th>
                <th>College</th>
                <th>Program</th>
                <th>Subject</th>
                <th>Section</th>
                <th>Professor</th>
                <th>Semester</th>
                <th>Eligibility</th>
                <th>Action</th>
            </tr>
            <?php
           $pending = $conn->query("
SELECT 
    e.id AS enrollment_id,
    e.semester,
    u.fullname,
    s.student_id,
    s.program,
    s.year_level,
    s.semester_level,
    d.department_name,
    c.course_code,
    c.title,
    cs.section,
    up.fullname AS professor_name,
    c.prerequisite_course_id,
    pre.course_code AS pre_code,
    CASE
        WHEN c.department_id <> s.department_id THEN 'Not eligible: wrong college'
        WHEN c.program <> s.program THEN 'Not eligible: wrong program'
        WHEN c.year_level <> s.year_level OR c.semester_level <> s.semester_level THEN 'Not eligible: wrong year/semester'
        WHEN c.prerequisite_course_id IS NOT NULL 
             AND NOT EXISTS (
                SELECT 1 
                FROM enrollments ep 
                WHERE ep.student_id = s.id 
                AND ep.course_id = c.prerequisite_course_id 
                AND ep.remarks = 'PASSED'
             ) THEN CONCAT('Not eligible: needs ', pre.course_code)
        ELSE 'Eligible'
    END AS eligibility
FROM enrollments e
INNER JOIN students s ON e.student_id = s.id
INNER JOIN users u ON s.user_id = u.id
INNER JOIN courses c ON e.course_id = c.id
LEFT JOIN courses pre ON c.prerequisite_course_id = pre.id
LEFT JOIN departments d ON s.department_id = d.id
LEFT JOIN course_sections cs ON e.section_id = cs.id
LEFT JOIN professors p ON cs.professor_id = p.id
LEFT JOIN users up ON p.user_id = up.id
WHERE e.status = 'Pending'
ORDER BY e.id DESC
");
            if ($pending && $pending->num_rows > 0):
                while($row = $pending->fetch_assoc()):
                    $isEligible = $row['eligibility'] === 'Eligible';
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_id'].' - '.$row['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['program']); ?></td>
                    <td><?php echo htmlspecialchars($row['course_code'].' - '.$row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['section'] ?? 'No section'); ?></td>
                    <td><?php echo htmlspecialchars($row['professor_name'] ?? 'No professor'); ?></td>
                    <td><?php echo htmlspecialchars($row['semester']); ?></td>
                    <td><span class="status <?php echo $isEligible ? '' : 'status-danger'; ?>"><?php echo htmlspecialchars($row['eligibility']); ?></span></td>
                    <td class="action-cell">
                        <?php if($isEligible): ?>
                            <a class="small-btn approve-btn" href="confirm_enrollment.php?action=approve&id=<?php echo $row['enrollment_id']; ?>" onclick="return confirm('Confirm this enrollment?');">Confirm</a>
                        <?php endif; ?>
                        <a class="small-btn reject-btn" href="confirm_enrollment.php?action=reject&id=<?php echo $row['enrollment_id']; ?>" onclick="return confirm('Reject this enrollment request?');">Reject</a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9" class="empty">No pending enrollment requests.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="content-card">
        <h2>Enrollment Records</h2>
        <table>
            <tr>
                <th>Student</th>
                <th>Subject</th>
                <th>Section</th>
                <th>Professor</th>
                <th>Semester</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
            <?php
            $query = "\n            SELECT users.fullname, students.student_id, courses.course_code, courses.title, cs.section, up.fullname AS professor_name, enrollments.semester, enrollments.status, enrollments.remarks\n            FROM enrollments\n            INNER JOIN students ON enrollments.student_id = students.id\n            INNER JOIN users ON students.user_id = users.id\n            INNER JOIN courses ON enrollments.course_id = courses.id\n            ORDER BY enrollments.id DESC\n            ";
            $result = $conn->query($query);
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $danger = in_array($row['status'], ['Rejected']) ? ' status-danger' : '';
                    echo "<tr>";
                    echo "<td>".htmlspecialchars($row['student_id'].' - '.$row['fullname'])."</td>";
                    echo "<td>".htmlspecialchars($row['course_code'].' - '.$row['title'])."</td>";
                    echo "<td>".htmlspecialchars($row['section'] ?? 'No section')."</td>";
                    echo "<td>".htmlspecialchars($row['professor_name'] ?? 'No professor')."</td>";
                    echo "<td>".htmlspecialchars($row['semester'])."</td>";
                    echo "<td><span class='status$danger'>".htmlspecialchars($row['status'])."</span></td>";
                    echo "<td>".htmlspecialchars($row['remarks'])."</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='empty'>No enrollment records yet.</td></tr>";
            }
            ?>
        </table>
    </div>
</div>
</body>
</html>
