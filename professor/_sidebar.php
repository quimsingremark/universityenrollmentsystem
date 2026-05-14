<?php include '../config/helpers.php'; ?>
<div class="sidebar"><h2>Professor</h2><p><?php echo e($_SESSION['fullname'] ?? ''); ?></p><a href="dashboard.php">Dashboard</a><a href="../auth/logout.php">Logout</a></div>
