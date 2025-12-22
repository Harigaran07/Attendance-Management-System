<?php
include 'config.php';
session_start();

require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_department = $_SESSION['department'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_attendance'])) {
    $year = $_POST['year'] ?? '';
    $date = $_POST['date'] ?? '';
    $period = $_POST['period'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $attendance_data = $_POST['attendance'] ?? [];

    if (empty($attendance_data) || empty($year) || empty($date) || empty($period) || empty($subject)) {
        $_SESSION['error'] = "Please fill all fields (Year, Date, Period, Subject)!";
        header("Location: mark_attendance.php?year=$year&date=$date");
        exit();
    }

    $errors = 0;
    $absent_students = [];

    // Save attendance for selected period
    foreach ($attendance_data as $register_no => $status) {
        if (empty($status)) continue;

        // Escape data
        $register_no = mysqli_real_escape_string($conn, $register_no);
        $status = mysqli_real_escape_string($conn, $status);

        // Insert or update attendance
        $query = "
            INSERT INTO attendance (register_no, department, year, date, period, subject, status, recorded_by)
            VALUES ('$register_no', '$staff_department', '$year', '$date', '$period', '$subject', '$status', '$staff_id')
            ON DUPLICATE KEY UPDATE 
                status='$status', 
                subject='$subject',
                recorded_by='$staff_id'
        ";

        if (!mysqli_query($conn, $query)) {
            $errors++;
        }

        // Track absentees
        if ($status === "Absent") {
            $student_query = "SELECT name, parent_email FROM students WHERE register_no='$register_no' LIMIT 1";
            $student_result = mysqli_query($conn, $student_query);
            if ($student = mysqli_fetch_assoc($student_result)) {
                $absent_students[$register_no] = [
                    'name' => $student['name'],
                    'parent_email' => $student['parent_email']
                ];
            }
        }
    }

    // --- SEND EMAIL ONLY ON PERIOD 4 OR 8 ---
    if (in_array($period, ['4', '8'])) {

        // Determine which range (1–4 or 5–8)
        $range_start = ($period == '4') ? 1 : 5;
        $range_end = ($period == '4') ? 4 : 8;

        // Find all absentees in that range
        $range_absentees = [];
        $absent_query = "
            SELECT a.register_no, s.name, s.parent_email, GROUP_CONCAT(a.period ORDER BY a.period) as absent_periods
            FROM attendance a
            JOIN students s ON a.register_no = s.register_no
            WHERE a.date = '$date' 
              AND a.department = '$staff_department' 
              AND a.year = '$year'
              AND a.period BETWEEN '$range_start' AND '$range_end'
              AND a.status = 'Absent'
            GROUP BY a.register_no
        ";
        $absent_result = mysqli_query($conn, $absent_query);

        while ($row = mysqli_fetch_assoc($absent_result)) {
            $range_absentees[] = $row;
        }

        // Send email for each absent student
        foreach ($range_absentees as $student) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'harigaransakthivel@gmail.com';
                $mail->Password = 'csoy gsth inwx pkyn';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('harigaransakthivel@gmail.com', 'College Attendance System');
                $mail->addAddress($student['parent_email']);

                $mail->isHTML(true);
                $mail->Subject = "Absence Report for {$student['name']} ($date)";
                $mail->Body = "
                    <p>Dear Parent,</p>
                    <p>Your child <strong>{$student['name']}</strong> (Register No: <strong>{$student['register_no']}</strong>) 
                    was marked <strong>Absent</strong> during period(s): <strong>{$student['absent_periods']}</strong> 
                    on <strong>$date</strong>.</p>
                    <p>Please ensure their attendance improves.</p>
                    <br><p>Regards,<br>College Attendance System</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Mail error for {$student['register_no']}: " . $mail->ErrorInfo);
            }
        }
    }

    $_SESSION['success'] = "Attendance for Period $period ($subject) saved successfully.";
    header("Location: mark_attendance.php?year=$year&date=$date");
    exit();
} else {
    $_SESSION['error'] = "Invalid request!";
    header("Location: mark_attendance.php");
    exit();
}
?>
