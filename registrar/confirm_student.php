<?php
session_start();
include '../config/database.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') { header("Location: ../auth/login.php"); exit(); }

$msg = '';
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'confirm') {
        $stmt = $conn->prepare("UPDATE users SET account_status='Confirmed' WHERE id=? AND role='student'");
        $stmt->bind_param("i", $id); $stmt->execute();
        $msg = 'Student confirmed successfully.';
    } elseif ($_GET['action'] == 'reject') {
        $stmt = $conn->prepare("UPDATE users SET account_status='Rejected' WHERE id=? AND role='student'");
        $stmt->bind_param("i", $id); $stmt->execute();
        $msg = 'Student registration rejected.';
    }
}
$pending = $conn->query("SELECT u.id AS user_id, u.fullname, u.login_id, u.email, u.account_status, s.year_level, s.semester_level, s.program, d.department_name FROM users u INNER JOIN students s ON u.id=s.user_id LEFT JOIN departments d ON s.department_id=d.id WHERE u.role='student' AND u.account_status='Pending' ORDER BY u.id DESC");
$all = $conn->query("SELECT u.fullname, u.login_id, u.email, u.account_status, s.year_level, s.semester_level, s.program, d.department_name FROM users u INNER JOIN students s ON u.id=s.user_id LEFT JOIN departments d ON s.department_id=d.id WHERE u.role='student' ORDER BY u.id DESC");
?>
<!DOCTYPE html>
<html><head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Confirm Students</title><link rel="stylesheet" href="../css/style.css"></head><body>
<div class="dashboard-container">
    <div class="topbar"><div><h1>Confirm Student Pre-Registration</h1><p>Registrar confirmation page</p></div><a class="logout-btn" href="../auth/logout.php">Logout</a></div>
    <div class="menu-card"><a href="dashboard.php">Dashboard</a><a href="confirm_students.php">Confirm Students</a><a href="assign_subject.php">Assign Subjects to Professors</a></div>
    <?php if($msg): ?><div class="message"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <div class="content-card">
        <h2>Pending Student Accounts</h2>
        <table><tr><th>ID Number</th><th>Name</th><th>Email</th><th>College</th><th>Program</th><th>Year/Sem</th><th>Action</th></tr>
        <?php if($pending && $pending->num_rows>0): while($r=$pending->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['login_id']); ?></td><td><?php echo htmlspecialchars($r['fullname']); ?></td><td><?php echo htmlspecialchars($r['email']); ?></td>
                <td><?php echo htmlspecialchars($r['department_name']); ?></td><td><?php echo htmlspecialchars($r['program']); ?></td><td>Year <?php echo htmlspecialchars($r['year_level']); ?> - Sem <?php echo htmlspecialchars($r['semester_level']); ?></td>
                <td class="action-cell"><a class="small-btn approve-btn" onclick="return confirm('Confirm this student?')" href="confirm_students.php?action=confirm&id=<?php echo $r['user_id']; ?>">Confirm</a> <a class="small-btn reject-btn" onclick="return confirm('Reject this student?')" href="confirm_students.php?action=reject&id=<?php echo $r['user_id']; ?>">Reject</a></td>
            </tr>
        <?php endwhile; else: ?><tr><td colspan="7" class="empty">No pending student pre-registrations.</td></tr><?php endif; ?>
        </table>
    </div>
    <div class="content-card"><h2>All Student Accounts</h2>
        <table><tr><th>ID Number</th><th>Name</th><th>College</th><th>Program</th><th>Status</th></tr>
        <?php if($all && $all->num_rows>0): while($r=$all->fetch_assoc()): $danger=$r['account_status']=='Rejected'?' status-danger':''; ?>
            <tr><td><?php echo htmlspecialchars($r['login_id']); ?></td><td><?php echo htmlspecialchars($r['fullname']); ?></td><td><?php echo htmlspecialchars($r['department_name']); ?></td><td><?php echo htmlspecialchars($r['program']); ?></td><td><span class="status<?php echo $danger; ?>"><?php echo htmlspecialchars($r['account_status']); ?></span></td></tr>
        <?php endwhile; endif; ?>
        </table>
    </div>
</div>
</body></html>
