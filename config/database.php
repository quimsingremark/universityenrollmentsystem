<?php
$conn = mysqli_connect(
    "sql103.infinityfree.com",
    "if0_41906032",
    "XRMebRZ5295Y",
    "if0_41906032_university_db"
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
