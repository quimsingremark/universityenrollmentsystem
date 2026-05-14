<?php
session_start();
include '../config/database.php';
include '../config/colleges.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../auth/login.php");
    exit();
}

ensureColleges($conn);
$message = "";

if(isset($_POST['submit'])) {
    $course_code = trim($_POST['course_code']);
    $title = trim($_POST['title']);
    $credits = intval($_POST['credits']);
    $department_id = intval($_POST['department_id']);
    $program = trim($_POST['program'] ?? 'BSCS');
    $year_level = intval($_POST['year_level'] ?? 1);
    $semester_level = intval($_POST['semester_level'] ?? 1);
    $prerequisite_course_id = !empty($_POST['prerequisite_course_id']) ? intval($_POST['prerequisite_course_id']) : null;

    $stmt = $conn->prepare("INSERT INTO courses(course_code, title, credits, department_id, program, prerequisite_course_id, year_level, semester_level) VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisiii", $course_code, $title, $credits, $department_id, $program, $prerequisite_course_id, $year_level, $semester_level);

    if($stmt->execute()) {
        $message = "Subject added successfully.";
    } else {
        $message = "Error: " . $conn->error;
    }
}

$prereqs = $conn->query("SELECT id, course_code, title FROM courses ORDER BY course_code");
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subject</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="form-container">
    <h1>Add Subject</h1>
    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <?php if($message != "") echo "<div class='message'>$message</div>"; ?>

    <form method="POST">
        <input type="text" name="course_code" placeholder="Subject Code e.g. ICT 102" required>
        <input type="text" name="title" placeholder="Subject Title" required>
        <input type="number" name="credits" placeholder="Units" min="1" required>

        <p class="help-text">Add the subject first. Then use <strong>Assign Subjects to Professors</strong> to assign professor and section.</p>

        <label>College / Department</label>
        <select name="department_id" id="department_id" required>
            <option value="">Select College</option>
            <?php collegeOptions($conn); ?>
        </select>

        <label>Course / Program</label>
        <select name="program" id="program" required>
            <option value="">Select college first</option>
        </select>

        <input type="number" name="year_level" placeholder="Year Level" value="1" min="1" max="4" required>
        <select name="semester_level" required>
            <option value="1">1st Semester</option>
            <option value="2">2nd Semester</option>
        </select>

        <label>Prerequisite Subject</label>
        <select name="prerequisite_course_id">
            <option value="">No prerequisite</option>
            <?php while($c = $prereqs->fetch_assoc()): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'].' - '.$c['title']); ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="submit">Add Subject</button>
    </form>
</div>

<script>
const departmentSelect = document.getElementById('department_id');
const programSelect = document.getElementById('program');

function loadPrograms(departmentId) {
    programSelect.innerHTML = '<option value="">Loading programs...</option>';

    if (!departmentId) {
        programSelect.innerHTML = '<option value="">Select college first</option>';
        return;
    }

    fetch('get_programs.php?department_id=' + encodeURIComponent(departmentId))
        .then(response => response.json())
        .then(programs => {
            programSelect.innerHTML = '<option value="">Select Course / Program</option>';

            if (programs.length === 0) {
                programSelect.innerHTML = '<option value="">No programs found</option>';
                return;
            }

            programs.forEach(program => {
                const option = document.createElement('option');
                option.value = program.program_code;
                option.textContent = program.program_code + ' - ' + program.program_name;
                programSelect.appendChild(option);
            });
        })
        .catch(() => {
            programSelect.innerHTML = '<option value="">Error loading programs</option>';
        });
}

departmentSelect.addEventListener('change', function () {
    loadPrograms(this.value);
});
</script>
</body>
</html>
