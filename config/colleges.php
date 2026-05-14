<?php
function academicColleges() {
    return [
        'College of Computer and Informatics (CCI)' => [
            'BSCS' => 'Bachelor of Science in Computer Science',
            'BSIT' => 'Bachelor of Science in Information Technology',
            'BSIS' => 'Bachelor of Science in Information System'
        ],
        'College of Education (COE)' => [
            'BEED' => 'Bachelor of Elementary Education',
            'BSED-FIL' => 'Bachelor of Secondary Education - Filipino',
            'BSED-MATH' => 'Bachelor of Secondary Education - Mathematics',
            'BSED-SCI' => 'Bachelor of Secondary Education - Science',
            'BSED-TLE' => 'Bachelor of Secondary Education - Technology and Livelihood Education (TLE)',
            'BTLED-HE' => 'Bachelor of Technology and Livelihood Education - Home Economics',
            'BTLED-IA' => 'Bachelor of Technology and Livelihood Education - Industrial Arts',
            'BTLED-ICT' => 'Bachelor of Technology and Livelihood Education - Information and Communication Technology',
            'BTVTED-BCW' => 'Bachelor of Technical-Vocational Teacher Education - Beauty Care and Wellness',
            'BTVTED-CP' => 'Bachelor of Technical-Vocational Teacher Education - Computer Programming',
            'BTVTED-FSM' => 'Bachelor of Technical-Vocational Teacher Education - Food and Service Management'
        ],
        'College of Engineering and Architecture (CEA)' => [
            'BSECE' => 'Bachelor of Science in Electronics Engineering',
            'BSARCH' => 'Bachelor of Science in Architecture',
            'BSEE' => 'Bachelor of Science in Electrical Engineering',
            'BSME-AE' => 'Bachelor of Science in Mechanical Engineering - Major in Automotive Engineering'
        ],
        'College of Industrial Technology (CIT)' => [
            'BIT-AD' => 'Bachelor of Science in Industrial Technology - Architectural Drafting',
            'BIT-AT' => 'Bachelor of Science in Industrial Technology - Automotive Technology',
            'BIT-CT' => 'Bachelor of Science in Industrial Technology - Construction Technology',
            'BIT-ET' => 'Bachelor of Science in Industrial Technology - Electrical Technology',
            'BIT-ELX' => 'Bachelor of Science in Industrial Technology - Electronics Technology',
            'BIT-FAT' => 'Bachelor of Science in Industrial Technology - Fashion and Apparel Technology',
            'BIT-FCMT' => 'Bachelor of Science in Industrial Technology - Furniture and Cabinet Making Technology',
            'BIT-MT' => 'Bachelor of Science in Industrial Technology - Mechanical Technology',
            'BIT-RACT' => 'Bachelor of Science in Industrial Technology - Refrigerator and Air Conditioning Technology',
            'BIT-WFT' => 'Bachelor of Science in Industrial Technology - Welding and Fabrication Technology',
            'BSAT' => 'Bachelor of Science in Automotive Technology',
            'BSHRT' => 'Bachelor of Science in Hotel and Restaurant Technology',
            'BSELT' => 'Bachelor of Science in Electrical Technology',
            'BSELX' => 'Bachelor of Science in Electronics Technology',
            'BSFDM' => 'Bachelor in Fashion Design and Merchandising',
            'BSENTREP' => 'Bachelor of Science in Entrepreneurship',
            'BSHM' => 'Bachelor of Science in Hospitality Management',
            'BSTM' => 'Bachelor of Science in Tourism Management'
        ],
        'College of Arts and Sciences (CAS)' => [
            'BAEL' => 'Bachelor of Arts in English Language',
            'BACD' => 'Bachelor of Arts in Community Development',
            'BSBIO-BIOTECH' => 'Bachelor of Science in Biology - Biotechnology',
            'BSMATH' => 'Bachelor of Science in Mathematics',
            'BSMET' => 'Bachelor of Science in Meteorology',
            'BHS' => 'Bachelor in Human Services'
        ]
    ];
}

function tableExists($conn, $table) {
    $table = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '$table'");
    return $r && $r->num_rows > 0;
}

function columnExists($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $r && $r->num_rows > 0;
}

function ensureColumnSafe($conn, $table, $column, $definition) {
    if (tableExists($conn, $table) && !columnExists($conn, $table, $column)) {
        $conn->query("ALTER TABLE `$table` ADD `$column` $definition");
    }
}

function ensureAcademicStructure($conn) {
    // Keep existing data. Only create missing tables/columns needed by Registrar/Professor reflection.
    $conn->query("CREATE TABLE IF NOT EXISTS departments (id INT AUTO_INCREMENT PRIMARY KEY, department_name VARCHAR(150) NOT NULL UNIQUE)");
    $conn->query("CREATE TABLE IF NOT EXISTS programs (id INT AUTO_INCREMENT PRIMARY KEY)");

    ensureColumnSafe($conn, 'programs', 'department_id', "INT NULL");
    ensureColumnSafe($conn, 'programs', 'program_code', "VARCHAR(40) NULL");
    ensureColumnSafe($conn, 'programs', 'program_name', "VARCHAR(255) NULL");
    if (tableExists($conn, 'students')) ensureColumnSafe($conn, 'students', 'program', "VARCHAR(50) DEFAULT 'BSCS'");
    if (tableExists($conn, 'courses')) ensureColumnSafe($conn, 'courses', 'program', "VARCHAR(50) DEFAULT 'BSCS'");
    if (tableExists($conn, 'professors')) ensureColumnSafe($conn, 'professors', 'department_id', "INT NULL");

    foreach (academicColleges() as $college => $programs) {
        $collegeEsc = $conn->real_escape_string($college);
        $res = $conn->query("SELECT id FROM departments WHERE department_name='$collegeEsc' LIMIT 1");
        if (!$res || $res->num_rows == 0) {
            $conn->query("INSERT INTO departments(department_name) VALUES('$collegeEsc')");
            $department_id = intval($conn->insert_id);
        } else {
            $department_id = intval($res->fetch_assoc()['id']);
        }

        foreach ($programs as $code => $name) {
            $codeEsc = $conn->real_escape_string($code);
            $nameEsc = $conn->real_escape_string($name);
            $res = $conn->query("SELECT id FROM programs WHERE program_code='$codeEsc' LIMIT 1");
            if (!$res || $res->num_rows == 0) {
                $conn->query("INSERT INTO programs(department_id, program_code, program_name) VALUES($department_id, '$codeEsc', '$nameEsc')");
            } else {
                $conn->query("UPDATE programs SET department_id=$department_id, program_name='$nameEsc' WHERE program_code='$codeEsc'");
            }
        }
    }
}

function ensureColleges($conn) { ensureAcademicStructure($conn); }

function getCollegeId($conn, $collegeName) {
    ensureAcademicStructure($conn);
    $stmt = $conn->prepare("SELECT id FROM departments WHERE department_name=? LIMIT 1");
    $stmt->bind_param("s", $collegeName);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r && $r->num_rows ? $r->fetch_assoc()['id'] : 0;
}

function collegeOptions($conn, $selected = '') {
    ensureAcademicStructure($conn);
    foreach (array_keys(academicColleges()) as $college) {
        $id = getCollegeId($conn, $college);
        $sel = ((string)$selected === (string)$id) ? 'selected' : '';
        echo '<option value="'.htmlspecialchars($id).'" '.$sel.'>'.htmlspecialchars($college).'</option>';
    }
}

function programOptions($conn, $selected = '') {
    ensureAcademicStructure($conn);
    foreach (academicColleges() as $college => $programs) {
        echo '<optgroup label="'.htmlspecialchars($college).'">';
        foreach ($programs as $code => $name) {
            $sel = ($selected === $code) ? 'selected' : '';
            echo '<option value="'.htmlspecialchars($code).'" '.$sel.'>'.htmlspecialchars($code.' - '.$name).'</option>';
        }
        echo '</optgroup>';
    }
}


function programOptionsByDepartment($conn, $department_id = 0, $selected = '') {
    ensureAcademicStructure($conn);
    $department_id = intval($department_id);
    if ($department_id > 0) {
        $stmt = $conn->prepare("SELECT program_code, program_name FROM programs WHERE department_id=? ORDER BY program_name");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $sel = ($selected === $row['program_code']) ? 'selected' : '';
            echo '<option value="'.htmlspecialchars($row['program_code']).'" '.$sel.'>'.htmlspecialchars($row['program_code'].' - '.$row['program_name']).'</option>';
        }
    } else {
        programOptions($conn, $selected);
    }
}

?>
