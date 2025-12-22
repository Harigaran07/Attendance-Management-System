<?php
session_start();
include 'config.php';
require 'vendor/autoload.php'; // PHPSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

date_default_timezone_set("Asia/Kolkata");

if (!isset($_SESSION['student_id']) || !isset($_SESSION['department'])) {
    header("Location: login.php");
    exit();
}


$staff_department = $_SESSION['department'];
$search_results = [];
$present_count = 0;
$absent_count = 0;
$message = "";

/**
 * Fetch attendance details (subject_name is joined from subjects table using subject code stored in attendance.subject)
 * Returns array of rows with fields: register_no, name, date, period, status, subject_name
 */
function getAttendanceDetails($conn, $department, $year, $from, $to, $register_no = "", $subject_name = "")
{
    $query = "
        SELECT a.register_no, s.name, a.date, a.period, a.status,
               COALESCE(sub.subject_name, '') AS subject_name
        FROM attendance a
        JOIN students s ON a.register_no = s.register_no
        LEFT JOIN subjects sub ON a.subject = sub.subject_code
        WHERE a.department = ?
          AND a.year = ?
          AND a.date BETWEEN ? AND ?
    ";

    $params = [$department, $year, $from, $to];
    $types = "ssss";

    if (!empty($register_no)) {
        $query .= " AND a.register_no = ?";
        $params[] = $register_no;
        $types .= "s";
    }

    if (!empty($subject_name)) {
        // if user selected a subject name, filter on subject_name (matching subjects table)
        $query .= " AND sub.subject_name = ?";
        $params[] = $subject_name;
        $types .= "s";
    }

    $query .= " ORDER BY a.date, a.period, a.register_no";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// -----------------------------
// Handle AJAX generation (no refresh)
// -----------------------------
if (isset($_POST['generate_report_ajax'])) {
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $year = $_POST['year'] ?? '';
    $register_no = trim($_POST['register_no'] ?? '');
    $subject_name = trim($_POST['subject'] ?? '');

    // basic validation
    if (!$from_date || !$to_date || !$year) {
        echo "<div class='alert alert-danger'>Please select From Date, To Date and Year.</div>";
        exit();
    }

    $search_results = getAttendanceDetails($conn, $staff_department, $year, $from_date, $to_date, $register_no, $subject_name);

    // Build table HTML
    ob_start();
    if (empty($search_results)) {
        echo "<div class='alert alert-warning'>No attendance records found for the selected criteria.</div>";
        echo ob_get_clean();
        exit();
    }

    // Determine whether subject column should be shown (subject_name filter selected)
    $showSubjectColumn = !empty($subject_name);

    // Table rows
    echo "<div class='table-responsive'>";
    echo "<table class='table table-bordered text-center'>";
    echo "<thead class='table-primary'><tr>";
    echo "<th>Register No</th><th>Name</th><th>Date</th>";
    if ($showSubjectColumn) echo "<th>Subject</th>";
    echo "<th>Period</th><th>Status</th></tr></thead><tbody>";

    foreach ($search_results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['register_no']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
        if ($showSubjectColumn) {
            echo "<td>" . ($row['subject_name'] ? htmlspecialchars($row['subject_name']) : '<span class=\"text-muted\">N/A</span>') . "</td>";
        }
        echo "<td>" . htmlspecialchars($row['period']) . "</td>";
        $cls = ($row['status'] === 'Present') ? 'text-success' : 'text-danger';
        echo "<td class='$cls'>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table></div>";

    // ---------------------------
    // Present / Absent overall counts (as earlier logic: per student per date)
    // ---------------------------
    $studentSummary = []; // [reg][date] => presentHours, absentHours
    foreach ($search_results as $r) {
        $reg = $r['register_no'];
        $date = $r['date'];
        if (!isset($studentSummary[$reg][$date])) $studentSummary[$reg][$date] = ['presentHours' => 0, 'absentHours' => 0];
        if ($r['status'] === 'Present') $studentSummary[$reg][$date]['presentHours']++;
        else $studentSummary[$reg][$date]['absentHours']++;
    }

    $present_students = [];
    $absent_students = [];
    foreach ($studentSummary as $reg => $dates) {
        foreach ($dates as $d => $info) {
            if ($info['presentHours'] > 0) $present_students[$reg . "_" . $d] = true;
            if ($info['absentHours'] == 8) $absent_students[$reg . "_" . $d] = true;
        }
    }

    $present_count = count($present_students);
    $absent_count = count($absent_students);

    echo "<div class='alert alert-info text-center fw-bold mt-3'>Present Students: {$present_count} | Absent Students (full-day): {$absent_count}</div>";

    // ---------------------------
    // If user selected ALL SUBJECTS (subject_name empty) -> build subject-wise summary:
    // For each subject present in the results, show Present/Absent counts and list absent register numbers.
    // Logic: For each subject, consider all attendance rows for that subject in the date range.
    // - For each student in that subject: if student has at least 1 'Present' record for that subject -> counted as present
    // - If student has 0 'Present' records but has at least 1 record for that subject in range -> counted as absent and included in absent list
    // ---------------------------
    if (empty($subject_name)) {
        // Build per-subject mapping
        $subjectMap = []; // subject_name => reg => ['presentCount'=>x, 'recordCount'=>y]
        foreach ($search_results as $r) {
            $sub = $r['subject_name'] ?: 'N/A';
            $reg = $r['register_no'];
            if (!isset($subjectMap[$sub][$reg])) $subjectMap[$sub][$reg] = ['presentCount' => 0, 'recordCount' => 0];
            if ($r['status'] === 'Present') $subjectMap[$sub][$reg]['presentCount']++;
            $subjectMap[$sub][$reg]['recordCount']++;
        }

        echo "<div class='mt-4'>";
        echo "<h5 class='text-primary'>Subject-wise summary (selected date range)</h5>";
        foreach ($subjectMap as $subName => $studentsMap) {
            $presentCnt = 0;
            $absentCnt = 0;
            $absentRegs = [];
            foreach ($studentsMap as $reg => $info) {
                if ($info['presentCount'] > 0) {
                    $presentCnt++;
                } else {
                    // had records for that subject but no present -> consider absent for that subject
                    $absentCnt++;
                    $absentRegs[] = $reg;
                }
            }
            echo "<div class='mb-3'>";
            echo "<strong>" . htmlspecialchars($subName) . ":</strong> Present: <strong>{$presentCnt}</strong> | Absent: <strong>{$absentCnt}</strong><br>";
            if ($absentCnt > 0) {
                echo "Absent Students: " . htmlspecialchars(implode(", ", $absentRegs));
            } else {
                echo "Absent Students: <span class='text-muted'>None</span>";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        // when specific subject selected, show subject-specific summary (same logic applied to only that subject)
        $subName = $subject_name;
        $studentsMap = [];
        foreach ($search_results as $r) {
            $reg = $r['register_no'];
            if (!isset($studentsMap[$reg])) $studentsMap[$reg] = ['presentCount' => 0, 'recordCount' => 0];
            if ($r['status'] === 'Present') $studentsMap[$reg]['presentCount']++;
            $studentsMap[$reg]['recordCount']++;
        }
        $presentCnt = 0; $absentCnt = 0; $absentRegs = [];
        foreach ($studentsMap as $reg => $info) {
            if ($info['presentCount'] > 0) $presentCnt++;
            else { $absentCnt++; $absentRegs[] = $reg; }
        }
        echo "<div class='mt-4'>";
        echo "<h5 class='text-primary'>Subject summary for <em>" . htmlspecialchars($subName) . "</em></h5>";
        echo "<div>Present: <strong>{$presentCnt}</strong> | Absent: <strong>{$absentCnt}</strong></div>";
        echo "<div class='mt-2'>Absent Students: " . ($absentCnt ? htmlspecialchars(implode(", ", $absentRegs)) : "<span class='text-muted'>None</span>") . "</div>";
        echo "</div>";
    }

    $html = ob_get_clean();
    echo $html;
    exit();
}

if (isset($_POST['download_excel'])) {
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $year = $_POST['year'] ?? '';
    $register_no = trim($_POST['register_no'] ?? '');
    $subject_name = trim($_POST['subject'] ?? '');

    if (!$from_date || !$to_date || !$year) {
        $_SESSION['error'] = "Please select From Date, To Date and Year.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $search_results = getAttendanceDetails($conn, $staff_department, $year, $from_date, $to_date, $register_no, $subject_name);

    if (empty($search_results)) {
        $_SESSION['error'] = "No attendance records for selected criteria.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    ob_clean();
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $includeSubjectCol = !empty($subject_name);

    // -----------------------------
    // Header Styling
    // -----------------------------
    $headers = ['Date', 'Register No', 'Name'];
    if ($includeSubjectCol) $headers[] = 'Subject';
    for ($i = 1; $i <= 8; $i++) $headers[] = "P{$i}";

    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '1', $h);
        $col++;
    }

    // Style header row
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['rgb' => '1E88E5']
        ]
    ];
    $sheet->getStyle('A1:' . chr(64 + count($headers)) . '1')->applyFromArray($headerStyle);

    // -----------------------------
    // Arrange rows
    // -----------------------------
    $studentData = [];
    foreach ($search_results as $r) {
        $date = $r['date'];
        $reg = $r['register_no'];
        $period = 'P' . $r['period'];
        $status = ($r['status'] === 'Present') ? 'P' : 'A';
        if (!isset($studentData[$date][$reg])) {
            $studentData[$date][$reg] = [
                'name' => $r['name'],
                'subject' => $r['subject_name'] ?: 'N/A',
                'P1' => '', 'P2' => '', 'P3' => '', 'P4' => '',
                'P5' => '', 'P6' => '', 'P7' => '', 'P8' => ''
            ];
        }
        $studentData[$date][$reg][$period] = $status;
    }

    $rowNum = 2;
    foreach ($studentData as $date => $students) {
        foreach ($students as $reg => $data) {
            $col = 'A';
            $sheet->setCellValue($col++ . $rowNum, $date);
            $sheet->setCellValueExplicit($col++ . $rowNum, $reg, DataType::TYPE_STRING);
            $sheet->setCellValue($col++ . $rowNum, $data['name']);
            if ($includeSubjectCol) $sheet->setCellValue($col++ . $rowNum, $data['subject']);

            for ($i = 1; $i <= 8; $i++) {
                $cell = $col++ . $rowNum;
                $status = $data['P' . $i];
                $sheet->setCellValue($cell, $status);

                // ✅ Apply color and bold font based on Present/Absent
if ($status === 'P') {
    $sheet->getStyle($cell)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '2E7D32'] // Green
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        ]
    ]);
} elseif ($status === 'A') {
    $sheet->getStyle($cell)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'C62828'] // Red
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        ]
    ]);
}

            }

            $rowNum++;
        }
    }

    // -----------------------------
    // Apply Borders and Auto-width
    // -----------------------------
    $sheet->getStyle('A1:' . chr(64 + count($headers)) . ($rowNum - 1))
        ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    foreach (range('A', chr(64 + count($headers))) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // ----------------------------------------------------
    // ✅ SUBJECT-WISE SUMMARY BELOW MAIN DATA
    // ----------------------------------------------------
    $rowNum += 2;
    $sheet->setCellValue("A{$rowNum}", "Subject-wise Summary");
    $sheet->getStyle("A{$rowNum}")->applyFromArray([
        'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '0D47A1']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'color' => ['rgb' => 'BBDEFB']
        ]
    ]);
    $rowNum++;

    $subjectMap = [];
    foreach ($search_results as $r) {
        $sub = $r['subject_name'] ?: 'N/A';
        $reg = $r['register_no'];
        if (!isset($subjectMap[$sub][$reg])) $subjectMap[$sub][$reg] = ['presentCount' => 0, 'recordCount' => 0];
        if ($r['status'] === 'Present') $subjectMap[$sub][$reg]['presentCount']++;
        $subjectMap[$sub][$reg]['recordCount']++;
    }

    foreach ($subjectMap as $subName => $studentsMap) {
        $presentCnt = 0;
        $absentCnt = 0;
        $absentRegs = [];
        foreach ($studentsMap as $reg => $info) {
            if ($info['presentCount'] > 0) $presentCnt++;
            else {
                $absentCnt++;
                $absentRegs[] = $reg;
            }
        }

        $sheet->setCellValue("A{$rowNum}", $subName);
        $sheet->setCellValue("B{$rowNum}", "Present: {$presentCnt}");
        $sheet->setCellValue("C{$rowNum}", "Absent: {$absentCnt}");
        $sheet->setCellValue("D{$rowNum}", "Absent Students: " . ($absentRegs ? implode(", ", $absentRegs) : "None"));

        // Style summary row
        $sheet->getStyle("A{$rowNum}:D{$rowNum}")->applyFromArray([
            'font' => ['bold' => false, 'size' => 11],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED]],
        ]);
        $rowNum++;
    }

    // ----------------------------------------------------
    // DOWNLOAD FILE
    // ----------------------------------------------------
    $fileName = "Attendance_Report_" . date("Ymd_His") . ".xlsx";
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header("Cache-Control: max-age=0");

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generate Attendance Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body {
    background: linear-gradient(135deg, #e3f2fd, #ffffff);
    font-family: 'Poppins', sans-serif;
}
.container { margin-top: 40px; }
.card { border-radius: 12px; padding: 20px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="text-primary"><i class="bi bi-clipboard-data"></i> Generate Attendance Report</h4>
            <a href="staff_dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-circle"></i> Back</a>
        </div>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form id="reportForm" method="POST">
            <div class="row g-3">
                <div class="col-md-3">
                    <label><b>From Date</b></label>
                    <input type="date" name="from_date" id="from_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label><b>To Date</b></label>
                    <input type="date" name="to_date" id="to_date" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label><b>Year</b></label>
                    <select name="year" id="year" class="form-control" required>
                        <option value="">Select Year</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label><b>Subject (Optional)</b></label>
                    <select name="subject" id="subject" class="form-control">
                        <option value="">All Subjects</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label><b>Register No</b></label>
                    <input type="text" name="register_no" id="register_no" class="form-control" placeholder="Optional">
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="button" id="generateBtn" class="btn btn-primary px-4 me-2"><i class="bi bi-search"></i> Generate</button>

                <!-- Excel submit uses normal POST (not AJAX); fields must be present in the form -->
                <button type="submit" name="download_excel" id="downloadExcelBtn" class="btn btn-success px-4">
                    <i class="bi bi-file-earmark-excel"></i> Download Excel
                </button>
            </div>
        </form>

        <div id="reportResult" class="mt-4"></div>
    </div>
</div>

<script>
$(function(){
    // Fetch subjects when year changes
    $('#year').on('change', function(){
        const year = $(this).val();
        $('#subject').html('<option value="">All Subjects</option>');
        if (!year) return;
        fetch('fetch_subjects.php?year=' + encodeURIComponent(year))
            .then(r => r.json())
            .then(data => {
                data.forEach(s => {
                    $('#subject').append(`<option value="${s.subject_name}">${s.subject_name}</option>`);
                });
            })
            .catch(() => {
                $('#subject').html('<option value="">All Subjects</option>');
            });
    });

    // Generate report via AJAX (no refresh)
    $('#generateBtn').on('click', function(){
        const frm = $('#reportForm');
        const payload = frm.serialize() + '&generate_report_ajax=1';
        $('#reportResult').html("<div class='text-center text-secondary'><i class='bi bi-hourglass-split'></i> Generating...</div>");
        $.post(window.location.href, payload, function(res){
            $('#reportResult').html(res);
            // keep form values intact (AJAX does not clear them)
        }).fail(function(){
            $('#reportResult').html("<div class='alert alert-danger'>Error generating report.</div>");
        });
    });

    // Optional: prevent Excel submit if required fields missing
    $('#downloadExcelBtn').on('click', function(e){
        const from = $('#from_date').val();
        const to = $('#to_date').val();
        const year = $('#year').val();
        if (!from || !to || !year) {
            e.preventDefault();
            alert('Please select From Date, To Date and Year before downloading Excel.');
        }
    });

    // Pre-populate subjects if year already selected (useful when user navigates back)
    const initialYear = $('#year').val();
    if (initialYear) { $('#year').trigger('change'); }
});
</script>
</body>
</html>
