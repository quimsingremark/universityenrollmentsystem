<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'professor') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$fullname = $_SESSION['fullname'] ?? 'Professor';
$login_id = $_SESSION['login_id'] ?? '';
$professor_id = 0;
$professor = null;

function hasTable($conn, $table) {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    return ($res && $res->num_rows > 0);
}
function hasColumn($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($res && $res->num_rows > 0);
}

/* Find the logged-in professor safely, even if your existing database is from an older version. */
if (hasTable($conn, 'professors')) {
    $profUserCol = hasColumn($conn, 'professors', 'user_id');
    $profEmpCol  = hasColumn($conn, 'professors', 'employee_id');
    $profSpecCol = hasColumn($conn, 'professors', 'specialization');
    $profDeptCol = hasColumn($conn, 'professors', 'department_id');
    $deptTable   = hasTable($conn, 'departments');

    if ($profUserCol) {
        $sql = "SELECT p.*";
        if ($deptTable && $profDeptCol) $sql .= ", d.department_name";
        $sql .= " FROM professors p";
        if ($deptTable && $profDeptCol) $sql .= " LEFT JOIN departments d ON p.department_id=d.id";
        $sql .= " WHERE p.user_id=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $professor = $res->fetch_assoc();
                $professor_id = intval($professor['id']);
            }
        }
    }

    if ($professor_id == 0 && $profEmpCol && $login_id != '') {
        $stmt = $conn->prepare("SELECT * FROM professors WHERE employee_id=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $login_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $professor = $res->fetch_assoc();
                $professor_id = intval($professor['id']);
            }
        }
    }
}

$totalSections = $totalStudents = $totalPending = $totalGraded = 0;
$professor_ids_for_query = [];
if ($professor_id > 0) $professor_ids_for_query[] = $professor_id;
if ($user_id > 0 && !in_array($user_id, $professor_ids_for_query)) $professor_ids_for_query[] = $user_id;
$professor_id_list = count($professor_ids_for_query) ? implode(',', array_map('intval', $professor_ids_for_query)) : '0';

if (hasTable($conn, 'course_sections') && hasColumn($conn, 'course_sections', 'professor_id')) {
    $r=$conn->query("SELECT COUNT(*) AS total FROM course_sections WHERE professor_id IN ($professor_id_list)"); $totalSections=$r?intval($r->fetch_assoc()['total']):0;
    if (hasTable($conn, 'enrollments') && hasColumn($conn, 'enrollments', 'section_id')) {
        $r=$conn->query("SELECT COUNT(*) AS total FROM enrollments e INNER JOIN course_sections cs ON e.section_id=cs.id WHERE cs.professor_id IN ($professor_id_list) AND e.status='Enrolled'"); $totalStudents=$r?intval($r->fetch_assoc()['total']):0;
        $r=$conn->query("SELECT COUNT(*) AS total FROM enrollments e INNER JOIN course_sections cs ON e.section_id=cs.id WHERE cs.professor_id IN ($professor_id_list) AND e.status='Enrolled' AND (e.grade IS NULL OR e.grade='')"); $totalPending=$r?intval($r->fetch_assoc()['total']):0;
        $r=$conn->query("SELECT COUNT(*) AS total FROM enrollments e INNER JOIN course_sections cs ON e.section_id=cs.id WHERE cs.professor_id IN ($professor_id_list) AND e.grade IS NOT NULL AND e.grade!=''"); $totalGraded=$r?intval($r->fetch_assoc()['total']):0;
    }
}
?>
<!DOCTYPE html>
<html><head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Professor Dashboard</title><link rel="stylesheet" href="../css/style.css"></head><body>
<div class="dashboard-container">
    <div class="topbar"><div><h1>Professor Dashboard</h1><p>Welcome, <?php echo htmlspecialchars($fullname); ?></p><?php if($professor): ?><p><?php echo htmlspecialchars(($professor['employee_id'] ?? $login_id)); ?> <?php echo !empty($professor['specialization']) ? ' • '.htmlspecialchars($professor['specialization']) : ''; ?> <?php echo !empty($professor['department_name']) ? ' • '.htmlspecialchars($professor['department_name']) : ''; ?></p><?php endif; ?></div><a class="logout-btn" href="../auth/logout.php">Logout</a></div>
    <div class="menu-card"><a href="dashboard.php">Dashboard</a><a href="upload_grade.php">Upload Grades</a></div>
    <div class="stats-grid"><div class="stat-card"><h3><?php echo $totalSections; ?></h3><p>Subject Sections</p></div><div class="stat-card"><h3><?php echo $totalStudents; ?></h3><p>Confirmed Students</p></div><div class="stat-card"><h3><?php echo $totalPending; ?></h3><p>Pending Grades</p></div><div class="stat-card"><h3><?php echo $totalGraded; ?></h3><p>Uploaded Grades</p></div></div>
    <div class="content-card"><h2>My Subject Sections</h2>
        <table><tr><th>Subject Code</th><th>Subject</th><th>Section</th><th>School Year</th><th>Semester</th></tr>
        <?php if(hasTable($conn,'course_sections') && hasTable($conn,'courses')): $sections=$conn->query("SELECT c.course_code,c.title,cs.section,cs.school_year,cs.semester_label FROM course_sections cs INNER JOIN courses c ON cs.course_id=c.id WHERE cs.professor_id IN ($professor_id_list) ORDER BY c.course_code, cs.section"); if($sections && $sections->num_rows>0): while($s=$sections->fetch_assoc()): ?>
            <tr><td><?php echo htmlspecialchars($s['course_code']); ?></td><td><?php echo htmlspecialchars($s['title']); ?></td><td><?php echo htmlspecialchars($s['section']); ?></td><td><?php echo htmlspecialchars($s['school_year']); ?></td><td><?php echo htmlspecialchars($s['semester_label']); ?></td></tr>
        <?php endwhile; else: ?><tr><td colspan="5" class="empty">No assigned subject sections yet.</td></tr><?php endif; else: ?><tr><td colspan="5" class="empty">Course section table not found. Please import/update the database SQL.</td></tr><?php endif; ?>
        </table>
    </div>
    <div class="content-card"><h2>My Confirmed Students</h2>
        <table><tr><th>Student ID</th><th>Student</th><th>Subject</th><th>Section</th><th>Grade</th><th>Remarks</th></tr>
        <?php if(hasTable($conn,'enrollments') && hasColumn($conn,'enrollments','section_id')): $q=$conn->query("SELECT st.student_id,u.fullname,c.course_code,c.title,cs.section,e.grade,e.remarks FROM enrollments e INNER JOIN students st ON e.student_id=st.id INNER JOIN users u ON st.user_id=u.id INNER JOIN course_sections cs ON e.section_id=cs.id INNER JOIN courses c ON e.course_id=c.id WHERE cs.professor_id IN ($professor_id_list) AND e.status='Enrolled' ORDER BY c.course_code, cs.section, u.fullname"); if($q && $q->num_rows>0): while($row=$q->fetch_assoc()): $remarks=$row['remarks']?:'ONGOING'; $danger=$remarks=='FAILED'?' status-danger':''; ?>
            <tr><td><?php echo htmlspecialchars($row['student_id']); ?></td><td><?php echo htmlspecialchars($row['fullname']); ?></td><td><?php echo htmlspecialchars($row['course_code'].' - '.$row['title']); ?></td><td><?php echo htmlspecialchars($row['section']); ?></td><td><?php echo htmlspecialchars($row['grade'] ?: 'Not yet graded'); ?></td><td><span class="status<?php echo $danger; ?>"><?php echo htmlspecialchars($remarks); ?></span></td></tr>
        <?php endwhile; else: ?><tr><td colspan="6" class="empty">No confirmed students yet.</td></tr><?php endif; else: ?><tr><td colspan="6" class="empty">No confirmed students yet.</td></tr><?php endif; ?>
        </table>
    </div>
</div>
</body></html>
