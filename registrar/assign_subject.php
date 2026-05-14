<?php
session_start();
include '../config/database.php';
include_once '../config/colleges.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') { header("Location: ../auth/login.php"); exit(); }

// FIX ONLY: make sure the subjects/courses table is ready and has subjects to show in the dropdown.
// This does not delete or remove existing data.
if (function_exists('ensureAcademicStructure')) { ensureAcademicStructure($conn); }

function registrarTableExists($conn, $table) {
    $table = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '$table'");
    return $r && $r->num_rows > 0;
}
function registrarColumnExists($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $r && $r->num_rows > 0;
}
function registrarAddColumnIfMissing($conn, $table, $column, $definition) {
    if (registrarTableExists($conn, $table) && !registrarColumnExists($conn, $table, $column)) {
        $conn->query("ALTER TABLE `$table` ADD `$column` $definition");
    }
}
function registrarEnsureSubjectTables($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(20),
        title VARCHAR(100),
        credits INT DEFAULT 3,
        professor_id INT NULL,
        department_id INT NULL,
        prerequisite_course_id INT NULL,
        year_level INT DEFAULT 1,
        semester_level INT DEFAULT 1,
        program VARCHAR(50) DEFAULT 'BSCS'
    )");
    registrarAddColumnIfMissing($conn, 'courses', 'course_code', "VARCHAR(20) NULL");
    registrarAddColumnIfMissing($conn, 'courses', 'title', "VARCHAR(100) NULL");
    registrarAddColumnIfMissing($conn, 'courses', 'credits', "INT DEFAULT 3");
    registrarAddColumnIfMissing($conn, 'courses', 'professor_id', "INT NULL");
    registrarAddColumnIfMissing($conn, 'courses', 'department_id', "INT NULL");
    registrarAddColumnIfMissing($conn, 'courses', 'prerequisite_course_id', "INT NULL");
    registrarAddColumnIfMissing($conn, 'courses', 'year_level', "INT DEFAULT 1");
    registrarAddColumnIfMissing($conn, 'courses', 'semester_level', "INT DEFAULT 1");
    registrarAddColumnIfMissing($conn, 'courses', 'program', "VARCHAR(50) DEFAULT 'BSCS'");

    $conn->query("CREATE TABLE IF NOT EXISTS course_sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        professor_id INT NOT NULL,
        section VARCHAR(50) NOT NULL,
        school_year VARCHAR(20) DEFAULT '2026-2027',
        semester_label VARCHAR(50) DEFAULT '1st Semester',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}
function registrarSeedSubjectsIfEmpty($conn) {
    $count = $conn->query("SELECT COUNT(*) AS total FROM courses");
    $total = ($count && $count->num_rows) ? intval($count->fetch_assoc()['total']) : 0;
    if ($total > 0) return;

    $department_id = function_exists('getCollegeId') ? intval(getCollegeId($conn, 'College of Computer and Informatics (CCI)')) : 1;
    $subjects = [
        ['ICT 102','INTRODUCTION TO COMPUTING',3,1,1], ['CS 1','PROGRAMMING LOGIC FORMULATION',3,1,1],
        ['GE 1 SS','UNDERSTANDING THE SELF',3,1,1], ['GE 4 MATH','MATHEMATICS IN THE MODERN WORLD',3,1,1],
        ['GE 5 ENG','PURPOSIVE COMMUNICATION',3,1,1], ['GE 8 SS','ETHICS',3,1,1],
        ['GE ELEC 9','PHILIPPINE POPULAR CULTURE',3,1,1], ['NSTP 1','ROTC 1/LTS 1/CWTS 1',3,1,1],
        ['PE 1','EDUCATIONAL GYMNASTICS',2,1,1], ['ICT 103','FUNDAMENTALS OF PROGRAMMING',3,1,2],
        ['ICT 105','DISCRETE STRUCTURE 1',3,1,2], ['ICT 106','SYSTEM FUNDAMENTALS',3,1,2],
        ['GE 2 SS','READINGS IN PHILIPPINE HISTORY',3,1,2], ['ENG 3','TECHNICAL WRITING WITH ORAL COMMUNICATION',3,1,2],
        ['GE 3 SS','THE CONTEMPORARY WORLD',3,1,2], ['PE 2','INDIVIDUAL/DUAL SPORTS',2,1,2],
        ['NSTP 2','ROTC 2/LTS 2/CWTS 2',3,1,2], ['ICT 104','INTERMEDIATE PROGRAMMING',3,2,1],
        ['ICT 113','NETWORKS AND COMMUNICATIONS',3,2,1], ['GE ELEC 7','GENDER AND SOCIETY',3,2,1],
        ['MATH 12','INTRODUCTION TO CALCULUS',3,2,1], ['MATH 110','ADVANCED STATISTICS',3,2,1],
        ['GE 7 SCI','SCIENCE, TECHNOLOGY AND SOCIETY',3,2,1], ['PE 3','RHYTHMIC ACTIVITIES',2,2,1],
        ['ICT 110','APPLICATIONS DEVELOPMENT AND EMERGING TECHNOLOGIES',3,2,2], ['ICT 111','OBJECT ORIENTED PROGRAMMING',3,2,2],
        ['ICT 112','OPERATING SYSTEMS',3,2,2], ['ICT 114','SOFTWARE ENGINEERING 1',3,2,2],
        ['ICT 107','DATA STRUCTURES AND ALGORITHMS',3,2,2], ['GE ELEC 1','ENVIRONMENTAL SCIENCE',3,2,2],
        ['PE 4','TEAM SPORTS/GAMES',2,2,2], ['ICT 123','WEB INFORMATION SYSTEMS',3,4,1],
        ['ICT 125','STUDENT INTERNSHIP PROGRAM (600 HOURS)',6,4,2], ['RIZAL','LIFE AND WORKS OF RIZAL',3,4,2]
    ];
    $stmt = $conn->prepare("INSERT INTO courses(course_code, title, credits, department_id, program, year_level, semester_level) VALUES(?,?,?,?,?,?,?)");
    if ($stmt) {
        foreach ($subjects as $s) {
            $code = $s[0];
            $title = $s[1];
            $credits = $s[2];
            $program = 'BSCS';
            $year_level = $s[3];
            $semester_level = $s[4];
            $stmt->bind_param("ssiisii", $code, $title, $credits, $department_id, $program, $year_level, $semester_level);
            $stmt->execute();
        }
    }
}
registrarEnsureSubjectTables($conn);
registrarSeedSubjectsIfEmpty($conn);

$message=''; $messageClass='message';
if(isset($_POST['submit'])){
    $course_id=intval($_POST['course_id']);
    $professor_id=intval($_POST['professor_id']);
    $section=trim($_POST['section']);
    $school_year=trim($_POST['school_year']);
    $semester_label=trim($_POST['semester_label']);
    if($course_id<=0 || $professor_id<=0 || $section==''){
        $message='Please complete all fields.'; $messageClass='message status-danger';
    } else {
        $check=$conn->prepare("SELECT id FROM course_sections WHERE course_id=? AND section=? AND school_year=? AND semester_label=? LIMIT 1");
        $check->bind_param("isss", $course_id, $section, $school_year, $semester_label); $check->execute();
        if($check->get_result()->num_rows>0){
            $message='This subject and section already exists for the selected school year/semester.'; $messageClass='message status-danger';
        } else {
            $stmt=$conn->prepare("INSERT INTO course_sections(course_id, professor_id, section, school_year, semester_label) VALUES(?,?,?,?,?)");
            $stmt->bind_param("iisss", $course_id, $professor_id, $section, $school_year, $semester_label);
            if($stmt->execute()) $message='Subject successfully assigned to professor and section.'; else { $message='Error: '.$conn->error; $messageClass='message status-danger'; }
        }
    }
}
$courses=$conn->query("SELECT c.id,c.course_code,c.title,c.program,c.year_level,c.semester_level FROM courses c ORDER BY c.program,c.year_level,c.semester_level,c.course_code");
$professors=$conn->query("SELECT p.id,u.fullname,p.employee_id FROM professors p INNER JOIN users u ON p.user_id=u.id ORDER BY u.fullname");
$assignments=$conn->query("SELECT cs.id, cs.section, cs.school_year, cs.semester_label, c.course_code, c.title, c.program, u.fullname AS professor_name, p.employee_id FROM course_sections cs INNER JOIN courses c ON cs.course_id=c.id INNER JOIN professors p ON cs.professor_id=p.id INNER JOIN users u ON p.user_id=u.id ORDER BY cs.id DESC");
?>
<!DOCTYPE html>
<html><head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Assign Subject to Professor</title><link rel="stylesheet" href="../css/style.css"></head><body>
<div class="dashboard-container">
    <div class="topbar"><div><h1>Assign Subjects to Professors</h1><p>A professor can handle one or multiple subjects, each separated by section.</p></div><a class="logout-btn" href="../auth/logout.php">Logout</a></div>
    <div class="menu-card"><a href="dashboard.php">Dashboard</a><a href="confirm_students.php">Confirm Students</a><a href="assign_subject.php">Assign Subjects</a></div>
    <?php if($message): ?><div class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <div class="content-card">
        <h2>New Subject Assignment</h2>
        <form method="POST" class="grid-form">
            <div><label>Subject</label><select name="course_id" required><option value="">Select subject</option><?php while($c=$courses->fetch_assoc()): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'].' - '.$c['title'].' | '.$c['program'].' Year '.$c['year_level'].' Sem '.$c['semester_level']); ?></option><?php endwhile; ?></select></div>
            <div><label>Professor</label><select name="professor_id" required><option value="">Select professor</option><?php while($p=$professors->fetch_assoc()): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['employee_id'].' - '.$p['fullname']); ?></option><?php endwhile; ?></select></div>
            <div><label>Section</label><input type="text" name="section" placeholder="Example: BSCS 1-A" required></div>
            <div><label>School Year</label><input type="text" name="school_year" value="2026-2027" required></div>
            <div><label>Semester</label><select name="semester_label" required><option>1st Semester</option><option>2nd Semester</option><option>Summer</option></select></div>
            <div><button type="submit" name="submit">Assign Subject</button></div>
        </form>
    </div>
    <div class="content-card">
        <h2>Assigned Subject Sections</h2>
        <table><tr><th>Subject</th><th>Section</th><th>Professor</th><th>School Year</th><th>Semester</th></tr>
        <?php if($assignments && $assignments->num_rows>0): while($a=$assignments->fetch_assoc()): ?>
            <tr><td><?php echo htmlspecialchars($a['course_code'].' - '.$a['title'].' ('.$a['program'].')'); ?></td><td><?php echo htmlspecialchars($a['section']); ?></td><td><?php echo htmlspecialchars($a['employee_id'].' - '.$a['professor_name']); ?></td><td><?php echo htmlspecialchars($a['school_year']); ?></td><td><?php echo htmlspecialchars($a['semester_label']); ?></td></tr>
        <?php endwhile; else: ?><tr><td colspan="5" class="empty">No subject assignments yet.</td></tr><?php endif; ?>
        </table>
    </div>
</div>
</body></html>
