<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

if(isset($_POST['submit'])) {
    $student_id = intval($_POST['student_id']);
    $course_id = intval($_POST['course_id']);

    $studentStmt = $conn->prepare("SELECT department_id, program, year_level, semester_level FROM students WHERE id=? LIMIT 1");
    $studentStmt->bind_param("i", $student_id);
    $studentStmt->execute();
    $student = $studentStmt->get_result()->fetch_assoc();

    if (!$student) {
        $message = "Student not found.";
    } else {
        $department_id = intval($student['department_id']);
        $program = $student['program'];
        $year_level = intval($student['year_level']);
        $semester_level = intval($student['semester_level']);
        $semester = "Year {$year_level} - Semester {$semester_level}";

        $courseStmt = $conn->prepare("SELECT prerequisite_course_id FROM courses WHERE id=? AND department_id=? AND program=? AND year_level=? AND semester_level=? LIMIT 1");
        $courseStmt->bind_param("iisii", $course_id, $department_id, $program, $year_level, $semester_level);
        $courseStmt->execute();
        $course = $courseStmt->get_result()->fetch_assoc();

        if (!$course) {
            $message = "This subject does not belong to the student's college/program/current semester.";
        } else {
            $already = $conn->prepare("SELECT id FROM enrollments WHERE student_id=? AND course_id=? LIMIT 1");
            $already->bind_param("ii", $student_id, $course_id);
            $already->execute();

            if ($already->get_result()->num_rows > 0) {
                $message = "Student is already enrolled in this subject.";
            } else {
                $prereq = $course['prerequisite_course_id'];
                $canEnroll = true;

                if (!empty($prereq)) {
                    $check = $conn->prepare("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND remarks='PASSED' LIMIT 1");
                    $check->bind_param("ii", $student_id, $prereq);
                    $check->execute();
                    if ($check->get_result()->num_rows == 0) {
                        $canEnroll = false;
                        $message = "Cannot enroll. Student has not passed the prerequisite subject.";
                    }
                }

                if ($canEnroll) {
                    $insert = $conn->prepare("INSERT INTO enrollments(student_id, course_id, semester, status, remarks) VALUES(?, ?, ?, 'Enrolled', 'ONGOING')");
                    $insert->bind_param("iis", $student_id, $course_id, $semester);
                    $message = $insert->execute() ? "Student enrolled successfully." : "Enrollment error: " . $conn->error;
                }
            }
        }
    }
}

$students = $conn->query("\nSELECT s.id, s.student_id, s.department_id, s.program, s.year_level, s.semester_level, u.fullname, d.department_name\nFROM students s\nINNER JOIN users u ON s.user_id = u.id\nLEFT JOIN departments d ON s.department_id = d.id\nORDER BY u.fullname\n");

$courses = $conn->query("\nSELECT c.id, c.course_code, c.title, c.credits, c.department_id, c.program, c.year_level, c.semester_level\nFROM courses c\nORDER BY c.program, c.year_level, c.semester_level, c.course_code\n");
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Student</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="form-container wide-form">
    <h1>Enroll Student</h1>
    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <?php if($message != "") echo "<div class='message'>" . htmlspecialchars($message) . "</div>"; ?>

    <form method="POST">
        <label>Student</label>
        <select name="student_id" id="student_id" required>
            <option value="">Select Student</option>
            <?php while($s = $students->fetch_assoc()): ?>
                <option value="<?php echo $s['id']; ?>"
                    data-department="<?php echo htmlspecialchars($s['department_id']); ?>"
                    data-program="<?php echo htmlspecialchars($s['program']); ?>"
                    data-year="<?php echo htmlspecialchars($s['year_level']); ?>"
                    data-semester="<?php echo htmlspecialchars($s['semester_level']); ?>">
                    <?php echo htmlspecialchars($s['student_id'].' - '.$s['fullname'].' | '.$s['department_name'].' | '.$s['program'].' Y'.$s['year_level'].' S'.$s['semester_level']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Subject</label>
        <select name="course_id" id="course_id" required>
            <option value="">Select student first</option>
            <?php while($c = $courses->fetch_assoc()): ?>
                <option value="<?php echo $c['id']; ?>"
                    data-department="<?php echo htmlspecialchars($c['department_id']); ?>"
                    data-program="<?php echo htmlspecialchars($c['program']); ?>"
                    data-year="<?php echo htmlspecialchars($c['year_level']); ?>"
                    data-semester="<?php echo htmlspecialchars($c['semester_level']); ?>">
                    <?php echo htmlspecialchars($c['course_code'].' - '.$c['title'].' | '.$c['program'].' Y'.$c['year_level'].' S'.$c['semester_level']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <p class="help-text">Only subjects from the selected student's college, program, year level, and semester will appear.</p>

        <button type="submit" name="submit">Enroll Student</button>
    </form>
</div>

<script>
const studentSelect = document.getElementById('student_id');
const courseSelect = document.getElementById('course_id');
const allCourseOptions = Array.from(courseSelect.querySelectorAll('option')).slice(1);

function filterSubjects() {
    const selected = studentSelect.options[studentSelect.selectedIndex];
    const department = selected.dataset.department;
    const program = selected.dataset.program;
    const year = selected.dataset.year;
    const semester = selected.dataset.semester;

    courseSelect.innerHTML = '<option value="">Select Subject</option>';

    if (!department || !program) {
        courseSelect.innerHTML = '<option value="">Select student first</option>';
        return;
    }

    let count = 0;
    allCourseOptions.forEach(option => {
        if (
            option.dataset.department === department &&
            option.dataset.program === program &&
            option.dataset.year === year &&
            option.dataset.semester === semester
        ) {
            courseSelect.appendChild(option.cloneNode(true));
            count++;
        }
    });

    if (count === 0) {
        courseSelect.innerHTML = '<option value="">No matching subjects found</option>';
    }
}

studentSelect.addEventListener('change', filterSubjects);
</script>
</body>
</html>
