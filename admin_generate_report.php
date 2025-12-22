<?php
session_start();
include 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

date_default_timezone_set("Asia/Kolkata");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- Fetch departments (distinct) ---
$departments = [];
$deptRes = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");
while ($r = $deptRes->fetch_assoc()) $departments[] = $r['department'];

// --- Fetch all subjects with department+year (for client-side filtering) ---
$subjects = [];
$subRes = $conn->query("SELECT subject_code, subject_name, department, year FROM subjects ORDER BY subject_name");
while ($r = $subRes->fetch_assoc()) $subjects[] = $r;

// Helper: get attendance rows (prepared)
function getAttendanceDetails($conn, $department, $year, $from, $to, $subject_code = "", $reg_no = "") {
    $sql = "SELECT a.register_no, s.name, a.department, a.date, a.period, a.status, a.subject AS subject_code, COALESCE(sub.subject_name,'') AS subject_name
            FROM attendance a
            JOIN students s ON a.register_no = s.register_no
            LEFT JOIN subjects sub ON a.subject = sub.subject_code
            WHERE a.department = ? AND a.year = ? AND a.date BETWEEN ? AND ?";
    $params = [$department, $year, $from, $to];
    $types = "ssss";

    if (!empty($subject_code)) {
        $sql .= " AND a.subject = ?";
        $params[] = $subject_code;
        $types .= "s";
    }
    if (!empty($reg_no)) {
        $sql .= " AND a.register_no = ?";
        $params[] = $reg_no;
        $types .= "s";
    }

    $sql .= " ORDER BY a.date, a.register_no, a.period";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Initialize variables for form defaults
$search_results = [];
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read inputs (basic sanitization)
    $from_date = trim($_POST['from_date'] ?? '');
    $to_date = trim($_POST['to_date'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $subject_selected = trim($_POST['subject'] ?? '');
    $register_no = trim($_POST['register_no'] ?? '');

    // Validate required
    if (!$from_date || !$to_date || !$year || !$department) {
        $error = "Please select From Date, To Date, Year and Department.";
    } else {
        // fetch attendance
        $search_results = getAttendanceDetails($conn, $department, $year, $from_date, $to_date, $subject_selected, $register_no);

        if (empty($search_results)) {
            $success = "No attendance records found for selected criteria.";
        } else {
            $success = "Found " . count($search_results) . " attendance rows.";
        }

        // If Excel download requested
        if (isset($_POST['download_excel']) && !empty($search_results)) {
            // build aggregated data rows: one row per date+register
            $rowsByKey = [];
            foreach ($search_results as $r) {
                $key = $r['date'] . '_' . $r['register_no'];
                if (!isset($rowsByKey[$key])) {
                    $rowsByKey[$key] = [
                        'date' => $r['date'],
                        'register_no' => $r['register_no'],
                        'name' => $r['name'],
                        'subject_name' => $r['subject_name'] ?: 'N/A',
                        'periods' => array_fill(1, 8, '')
                    ];
                }
                $p = (int)$r['period'];
                if ($p >= 1 && $p <= 8) {
                    $rowsByKey[$key]['periods'][$p] = ($r['status'] === 'Present') ? 'P' : 'A';
                }
            }

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Attendance Report');

            // Build headers (always include Subject column - user asked subject name everywhere)
            $headers = ['Date', 'Register No', 'Name', 'Subject'];
            for ($i = 1; $i <= 8; $i++) $headers[] = "P{$i}";

            // Write header row
            $headerRow = 1;
            $col = 'A';
            foreach ($headers as $h) {
                $sheet->setCellValue($col . $headerRow, $h);
                $sheet->getStyle($col . $headerRow)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle($col . $headerRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1976D2');
                $sheet->getStyle($col . $headerRow)->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($col . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . $headerRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }

            // Write data rows
            $rowNum = $headerRow + 1;
            foreach ($rowsByKey as $d) {
                $col = 'A';
                $sheet->setCellValue($col++ . $rowNum, $d['date']);
                $sheet->setCellValueExplicit($col++ . $rowNum, $d['register_no'], DataType::TYPE_STRING);
                $sheet->setCellValue($col++ . $rowNum, $d['name']);
                $sheet->setCellValue($col++ . $rowNum, $d['subject_name']);

                for ($i = 1; $i <= 8; $i++) {
                    $cell = $col . $rowNum;
                    $val = $d['periods'][$i];
                    $sheet->setCellValue($cell, $val);
                    // style P in green bold, A in red bold
                    if ($val === 'P') {
                        $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('2E7D32');
                    } elseif ($val === 'A') {
                        $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('C62828');
                    }
                    $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $col++;
                }

                // row border for date/name etc
                $sheet->getStyle('A' . $rowNum . ':' . chr(ord('A') + count($headers) - 1) . $rowNum)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $rowNum++;
            }

            // Subject-wise summary calculation (students counted per subject)
            // We want: for each subject -> number of students present (have at least one P in range for that subject),
            // and absent students (have records but zero P).
            $summary = [];
            foreach ($search_results as $r) {
                $sub = $r['subject_name'] ?: 'Unknown';
                $regno = $r['register_no'];
                if (!isset($summary[$sub][$regno])) $summary[$sub][$regno] = ['presentCount' => 0, 'recordCount' => 0];
                if ($r['status'] === 'Present') $summary[$sub][$regno]['presentCount']++;
                $summary[$sub][$regno]['recordCount']++;
            }

            // Move pointer two rows after last data row
            $rowNum += 1;
            $sheet->setCellValue("A{$rowNum}", "Subject-wise Summary");
            $sheet->getStyle("A{$rowNum}")->getFont()->setBold(true)->setSize(12);
            $rowNum++;

            // header for summary table
            $sheet->setCellValue("A{$rowNum}", "Subject");
            $sheet->setCellValue("B{$rowNum}", "Present Count (students)");
            $sheet->setCellValue("C{$rowNum}", "Absent Count (students)");
            $sheet->setCellValue("D{$rowNum}", "Absent Register Nos");
            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getFont()->setBold(true);
            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BBDEFB');
            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rowNum++;

            // If a single subject selected, we still show summary but for that subject only
            if (!empty($subject_selected)) {
                $subName = null;
                // find subject name from subjects list if possible
                foreach ($subjects as $s) {
                    if ($s['subject_code'] === $subject_selected) { $subName = $s['subject_name']; break; }
                }
                $displaySub = $subName ?? ($rowsByKey ? reset($rowsByKey)['subject_name'] : 'Selected Subject');

                // build present/absent students for that subject only
                $perStudent = [];
                foreach ($search_results as $r) {
                    if (($r['subject_code'] ?? '') !== $subject_selected) continue;
                    $regno = $r['register_no'];
                    if (!isset($perStudent[$regno])) $perStudent[$regno] = ['present'=>0,'records'=>0];
                    if ($r['status'] === 'Present') $perStudent[$regno]['present']++;
                    $perStudent[$regno]['records']++;
                }
                $presentCnt = 0; $absentCnt = 0; $absentRegs = [];
                foreach ($perStudent as $regno => $info) {
                    if ($info['present'] > 0) $presentCnt++; else { $absentCnt++; $absentRegs[] = $regno; }
                }
                $sheet->setCellValue("A{$rowNum}", $displaySub);
                $sheet->setCellValue("B{$rowNum}", $presentCnt);
                $sheet->setCellValue("C{$rowNum}", $absentCnt);
                $sheet->setCellValue("D{$rowNum}", implode(", ", $absentRegs));
                $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $rowNum++;
            } else {
                // all subjects: iterate summary map
                foreach ($summary as $sub => $students) {
                    $presentCnt = 0; $absentCnt = 0; $absentRegs = [];
                    foreach ($students as $regno => $info) {
                        if ($info['presentCount'] > 0) $presentCnt++; else { $absentCnt++; $absentRegs[] = $regno; }
                    }
                    $sheet->setCellValue("A{$rowNum}", $sub);
                    $sheet->setCellValue("B{$rowNum}", $presentCnt);
                    $sheet->setCellValue("C{$rowNum}", $absentCnt);
                    $sheet->setCellValue("D{$rowNum}", implode(", ", $absentRegs));
                    $sheet->getStyle("A{$rowNum}:D{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $rowNum++;
                }
            }

            // Auto-size all used columns
            $highestCol = chr(ord('A') + count($headers) - 1);
            for ($c = 'A'; $c <= $highestCol; $c++) {
                $sheet->getColumnDimension($c)->setAutoSize(true);
            }
            // also ensure summary columns are auto-sized (A..D)
            foreach (['A','B','C','D'] as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

            // Send file
            $fileName = "Admin_Attendance_Report_" . date("Ymd_His") . ".xlsx";
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename=\"$fileName\"");
            $writer = new Xlsx($spreadsheet);
            $writer->save("php://output");
            exit();
        } // end download excel
    } // end else (no error)
} // end POST

// small helper to get subject name from code
function subjectNameFromCode($subjects, $code) {
    foreach ($subjects as $s) {
        if ($s['subject_code'] === $code) return $s['subject_name'];
    }
    return $code;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Generate Attendance Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f6fbff; font-family: Poppins, sans-serif; }
.card { border-radius: 12px; }
.table thead { background: linear-gradient(90deg,#1565C0,#42A5F5); color: #fff; }
.table .table-secondary th { background: #f1f7ff; }
.summary-box { background: #fff; border-radius: 8px; padding: 15px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); }
</style>
</head>
<body>
<div class="container mt-5">
    <div class="card p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 text-primary">📊 Admin Attendance Report Generator</h4>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">← Back</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" id="reportForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label>From Date</label>
                    <input type="date" name="from_date" value="<?= htmlspecialchars($_POST['from_date'] ?? '') ?>" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label>To Date</label>
                    <input type="date" name="to_date" value="<?= htmlspecialchars($_POST['to_date'] ?? '') ?>" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Year</label>
                    <select name="year" id="year" class="form-select" required>
                        <option value="">Select Year</option>
                        <?php foreach (['1st Year','2nd Year','3rd Year','4th Year'] as $y): 
                            $sel = (($_POST['year'] ?? '') == $y) ? 'selected' : ''; ?>
                            <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Department</label>
                    <select name="department" id="department" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach ($departments as $d):
                            $sel = (($_POST['department'] ?? '') == $d) ? 'selected' : ''; ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $sel ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Subject (Optional)</label>
                    <select name="subject" id="subject" class="form-select">
                        <option value="">All Subjects</option>
                        <?php
                        // populate subject options filtered by selected dept+year (fixes the extra-year issue)
                        $selDept = $_POST['department'] ?? '';
                        $selYear = $_POST['year'] ?? '';
                        $selSub = $_POST['subject'] ?? '';
                        foreach ($subjects as $s) {
                            if ($selDept && $selYear) {
                                if ($s['department'] !== $selDept || $s['year'] !== $selYear) continue;
                            }
                            $sel = ($s['subject_code'] === $selSub) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($s['subject_code']) . "\" $sel>" . htmlspecialchars($s['subject_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Register No (Optional)</label>
                    <input type="text" name="register_no" value="<?= htmlspecialchars($_POST['register_no'] ?? '') ?>" class="form-control">
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary me-2">Generate</button>
                <?php if (!empty($search_results)): ?>
                    <button type="submit" name="download_excel" class="btn btn-success">Download Excel</button>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!empty($search_results)): ?>
            <div class="summary-box mt-4">
                <h5 class="text-success mb-3">Report Results (<?= htmlspecialchars($_POST['department'] ?? '') ?> - <?= htmlspecialchars($_POST['year'] ?? '') ?>)</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center mb-3">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Register No</th>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Period</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['date']) ?></td>
                                    <td><?= htmlspecialchars($r['register_no']) ?></td>
                                    <td><?= htmlspecialchars($r['name']) ?></td>
                                    <td><?= htmlspecialchars($r['subject_name'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['period']) ?></td>
                                    <td class="<?= $r['status'] === 'Present' ? 'text-success fw-bold' : 'text-danger fw-bold' ?>"><?= htmlspecialchars($r['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                // Build subject-wise summary for display (both for all subjects and for a single subject)
                // Map: subject => regno => counts
                $subSummary = [];
                foreach ($search_results as $r) {
                    $sub = $r['subject_name'] ?: 'Unknown';
                    $regno = $r['register_no'];
                    if (!isset($subSummary[$sub][$regno])) $subSummary[$sub][$regno] = ['presentCount' => 0, 'recordCount' => 0];
                    if ($r['status'] === 'Present') $subSummary[$sub][$regno]['presentCount']++;
                    $subSummary[$sub][$regno]['recordCount']++;
                }
                ?>

                <h6 class="mt-3 fw-bold">📘 Subject-wise Summary</h6>
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead class="table-secondary">
                            <tr>
                                <th>Subject</th>
                                <th>Present Count (students)</th>
                                <th>Absent Count (students)</th>
                                <th>Absent Register Nos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // If a particular subject was selected, only show that subject row
                            if (!empty($_POST['subject'])) {
                                // find subject name
                                $code = $_POST['subject'];
                                $sname = subjectNameFromCode($subjects, $code) ?: 'Selected Subject';
                                $presentCnt = 0; $absentCnt = 0; $absentRegs = [];
                                $students = $subSummary[$sname] ?? [];
                                foreach ($students as $regno => $info) {
                                    if ($info['presentCount'] > 0) $presentCnt++; else { $absentCnt++; $absentRegs[] = $regno; }
                                }
                                echo "<tr>
                                        <td>" . htmlspecialchars($sname) . "</td>
                                        <td>{$presentCnt}</td>
                                        <td>{$absentCnt}</td>
                                        <td>" . ($absentRegs ? htmlspecialchars(implode(', ', $absentRegs)) : '<span class="text-muted">None</span>') . "</td>
                                      </tr>";
                            } else {
                                // all subjects
                                foreach ($subSummary as $sname => $students) {
                                    $presentCnt = 0; $absentCnt = 0; $absentRegs = [];
                                    foreach ($students as $regno => $info) {
                                        if ($info['presentCount'] > 0) $presentCnt++; else { $absentCnt++; $absentRegs[] = $regno; }
                                    }
                                    echo "<tr>
                                            <td>" . htmlspecialchars($sname) . "</td>
                                            <td>{$presentCnt}</td>
                                            <td>{$absentCnt}</td>
                                            <td>" . ($absentRegs ? htmlspecialchars(implode(', ', $absentRegs)) : '<span class="text-muted">None</span>') . "</td>
                                          </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// When department or year changes, submit so subject dropdown is filtered (keeps behavior simple)
document.getElementById('department').addEventListener('change', function(){ document.getElementById('reportForm').submit(); });
document.getElementById('year').addEventListener('change', function(){ document.getElementById('reportForm').submit(); });
</script>
</body>
</html>
