<?php include '../config/helpers.php'; ?>
<div class="sidebar"><h2>Student Portal</h2><p><?php echo e($_SESSION['fullname'] ?? ''); ?></p><a href="dashboard.php">Dashboard</a><a href="subjects_i_need.php">Subjects I Need To Take</a><a href="my_courses.php">My Subjects</a><a href="../auth/logout.php">Logout</a></div>
