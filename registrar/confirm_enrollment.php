<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../auth/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
action:
$action = $_GET['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve','reject'])) {
    header("Location: dashboard.php?msg=Invalid request");
    exit();
}

$stmt = $conn->prepare("\nSELECT e.id, e.student_id, e.course_id, e.status, s.department_id, s.program, s.year_level, s.semester_level, c.course_code, c.title, c.prerequisite_course_id, c.department_id AS course_department, c.program AS course_program, c.year_level AS course_year, c.semester_level AS course_semester\nFROM enrollments e\nINNER JOIN students s ON e.student_id = s.id\nINNER JOIN courses c ON e.course_id = c.id\nWHERE e.id = ? LIMIT 1\n");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header("Location: dashboard.php?msg=Enrollment request not found");
    exit();
}

if ($row['status'] !== 'Pending') {
    header("Location: dashboard.php?msg=This request is already processed");
    exit();
}

if ($action === 'reject') {
    $upd = $conn->prepare("UPDATE enrollments SET status='Rejected', remarks='REJECTED BY REGISTRAR' WHERE id=?");
    $upd->bind_param("i", $id);
    $upd->execute();
    header("Location: dashboard.php?msg=Enrollment request rejected");
    exit();
}

$eligible = true;
$reason = '';

if (intval($row['department_id']) !== intval($row['course_department'])) {
    $eligible = false;
    $reason = 'Subject is not under the student college.';
}

if ($eligible && $row['program'] !== $row['course_program']) {
    $eligible = false;
    $reason = 'Subject is not under the student program/course.';
}

if ($eligible && (intval($row['year_level']) !== intval($row['course_year']) || intval($row['semester_level']) !== intval($row['course_semester']))) {
    $eligible = false;
    $reason = 'Subject is not for the student current year/semester.';
}

if ($eligible && !empty($row['prerequisite_course_id'])) {
    $pre = intval($row['prerequisite_course_id']);
    $preCheck = $conn->prepare("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND remarks='PASSED' LIMIT 1");
    $preCheck->bind_param("ii", $row['student_id'], $pre);
    $preCheck->execute();
    if ($preCheck->get_result()->num_rows == 0) {
        $eligible = false;
        $reason = 'Student has not passed the prerequisite subject.';
    }
}

if ($eligible) {
    $upd = $conn->prepare("UPDATE enrollments SET status='Enrolled', remarks='ONGOING' WHERE id=?");
    $upd->bind_param("i", $id);
    $upd->execute();
    header("Location: dashboard.php?msg=Enrollment confirmed. Student is eligible.");
    exit();
}

$upd = $conn->prepare("UPDATE enrollments SET status='Rejected', remarks=? WHERE id=?");
$remarks = 'NOT ELIGIBLE: ' . $reason;
$upd->bind_param("si", $remarks, $id);
$upd->execute();
header("Location: dashboard.php?msg=Enrollment rejected: " . urlencode($reason));
exit();
?>
