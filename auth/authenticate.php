<?php
session_start();
include '../config/database.php';

$login_id = trim($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM users WHERE login_id = ? LIMIT 1");
$stmt->bind_param("s", $login_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        $status = $user['account_status'] ?? 'Confirmed';
        if ($user['role'] == 'student' && $status != 'Confirmed') {
            echo "Your student account is still " . htmlspecialchars($status) . ". Please wait for registrar confirmation.";
            exit();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_id'] = $user['login_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['fullname'] = $user['fullname'];
        if ($user['role'] == 'student') { header("Location: ../student/dashboard.php"); exit(); }
        if ($user['role'] == 'professor') { header("Location: ../professor/dashboard.php"); exit(); }
        if ($user['role'] == 'registrar') { header("Location: ../registrar/dashboard.php"); exit(); }
    } else {
        echo "Invalid password";
    }
} else {
    echo "ID number not found";
}
?>
