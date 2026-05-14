<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-container">
    <h2>University Enrollment System</h2>
    <p class="login-subtitle">Login using your ID number</p>

    <form action="authenticate.php" method="POST">
        <input type="text" name="login_id" placeholder="ID Number e.g. 2026-0001-A" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p class="login-register-link">New student? <a href="register.php">Pre-register here</a></p>

    <div class="demo-accounts">
        <strong>Demo Login</strong><br>
        Registrar: REG-0001 / admin123<br>
        Student: 2026-0001-A / student123<br>
        Professor: PROF-0001 / prof123
    </div>
</div>

</body>
</html>
