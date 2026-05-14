<?php
include '../config/database.php';
include '../config/colleges.php';

ensureAcademicStructure($conn);
$department_id = intval($_GET['department_id'] ?? 0);
header('Content-Type: application/json');

$programs = [];
if ($department_id > 0) {
    $stmt = $conn->prepare("SELECT program_code, program_name FROM programs WHERE department_id=? ORDER BY program_name");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
}

echo json_encode($programs);
?>
