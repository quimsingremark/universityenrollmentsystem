<?php
session_start();
include '../config/database.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'professor') { header("Location: ../auth/login.php"); exit(); }
$user_id=intval($_SESSION['user_id']); $fullname=$_SESSION['fullname'] ?? 'Professor'; $message=''; $messageClass='message';
function hasTable($conn, $table) { $table=$conn->real_escape_string($table); $res=$conn->query("SHOW TABLES LIKE '$table'"); return ($res && $res->num_rows>0); }
function hasColumn($conn, $table, $column) { $table=$conn->real_escape_string($table); $column=$conn->real_escape_string($column); $res=$conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'"); return ($res && $res->num_rows>0); }
$professor_match_id=0;
$professor_ids_for_query=[];
if(hasTable($conn,'professors')){
    if(hasColumn($conn,'professors','user_id')){
        $stmt=$conn->prepare("SELECT id FROM professors WHERE user_id=? LIMIT 1");
        if($stmt){ $stmt->bind_param("i", $user_id); $stmt->execute(); $r=$stmt->get_result(); if($r && $r->num_rows>0) $professor_match_id=intval($r->fetch_assoc()['id']); }
    }
    if($professor_match_id==0 && isset($_SESSION['login_id']) && hasColumn($conn,'professors','employee_id')){
        $login_id=$_SESSION['login_id'];
        $stmt=$conn->prepare("SELECT id FROM professors WHERE employee_id=? LIMIT 1");
        if($stmt){ $stmt->bind_param("s", $login_id); $stmt->execute(); $r=$stmt->get_result(); if($r && $r->num_rows>0) $professor_match_id=intval($r->fetch_assoc()['id']); }
    }
}
if($professor_match_id>0) $professor_ids_for_query[]=$professor_match_id;
if($user_id>0 && !in_array($user_id,$professor_ids_for_query)) $professor_ids_for_query[]=$user_id;
$professor_id_list=count($professor_ids_for_query)?implode(',',array_map('intval',$professor_ids_for_query)):'0';
if(isset($_POST['submit']) && $professor_match_id>0){
    $enrollment_id=intval($_POST['enrollment_id']); $grade=trim($_POST['grade']);
    $allowed=$conn->prepare("SELECT e.id FROM enrollments e INNER JOIN course_sections cs ON e.section_id=cs.id WHERE e.id=? AND cs.professor_id IN ($professor_id_list) AND e.status='Enrolled' LIMIT 1");
    $allowed->bind_param("i", $enrollment_id); $allowed->execute();
    if($allowed->get_result()->num_rows>0){
        $numeric=is_numeric($grade)?floatval($grade):null; $remarks=($numeric!==null && $numeric<=3.0)?'PASSED':'FAILED';
        $upd=$conn->prepare("UPDATE enrollments SET grade=?, remarks=? WHERE id=?"); $upd->bind_param("ssi", $grade, $remarks, $enrollment_id);
        if($upd->execute()) $message='Grade uploaded successfully.'; else { $message='Error updating grade: '.$conn->error; $messageClass='message status-danger'; }
    } else { $message='You are not allowed to grade this student/section.'; $messageClass='message status-danger'; }
}
?>
<!DOCTYPE html>
<html><head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Upload Grades</title><link rel="stylesheet" href="../css/style.css"></head><body>
<div class="dashboard-container">
    <div class="topbar"><div><h1>Upload Grades</h1><p>Professor: <?php echo htmlspecialchars($fullname); ?></p></div><a class="logout-btn" href="../auth/logout.php">Logout</a></div>
    <div class="menu-card"><a href="dashboard.php">Dashboard</a><a href="upload_grade.php">Upload Grades</a></div>
    <div class="content-card"><h2>Select Student, Subject, and Section</h2>
        <?php if($message): ?><div class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if($professor_match_id==0): ?><p class="empty">Professor profile not found.</p><?php else: ?>
        <form method="POST"><label>Enrollment Record</label><select name="enrollment_id" required><option value="">Select student / subject / section</option>
        <?php $q=$conn->query("SELECT e.id, st.student_id, u.fullname, c.course_code, c.title, cs.section, e.grade FROM enrollments e INNER JOIN students st ON e.student_id=st.id INNER JOIN users u ON st.user_id=u.id INNER JOIN course_sections cs ON e.section_id=cs.id INNER JOIN courses c ON e.course_id=c.id WHERE cs.professor_id IN ($professor_id_list) AND e.status='Enrolled' ORDER BY c.course_code, cs.section, u.fullname"); if($q && $q->num_rows>0): while($row=$q->fetch_assoc()): $current=$row['grade']?' | Current Grade: '.$row['grade']:' | Not yet graded'; ?>
            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['student_id'].' - '.$row['fullname'].' | '.$row['course_code'].' - '.$row['title'].' | Section: '.$row['section'].$current); ?></option>
        <?php endwhile; endif; ?>
        </select><label>Grade</label><input type="text" name="grade" placeholder="Example: 1.25, 2.00, 3.00, 5.00" required><p class="help-text">Grades 3.00 and below are PASSED. Above 3.00 is FAILED.</p><button type="submit" name="submit">Upload Grade</button></form><?php endif; ?>
    </div>
</div>
</body></html>
