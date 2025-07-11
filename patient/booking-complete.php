<?php
/* -------------------------------------------------
   booking-complete.php
   ------------------------------------------------- */

session_start();

/* ---------- 1.  Guard‑clause: patient must be logged in ---------- */
if (
    !isset($_SESSION['user']) ||
    $_SESSION['user'] === ''   ||
    $_SESSION['usertype'] !== 'p'
) {
    header('Location: ../login.php');
    exit;
}

$useremail = $_SESSION['user'];

/* ---------- 2.  Load database & Composer autoloader ---------- */
require_once '../connection.php';                    // gives $database (MySQLi)
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ---------- 3.  Helper: send the confirmation mail ---------- */
function sendAppointmentEmail(
    string $toEmail,
    string $toName,
    string $apponum,
    string $docname,
    string $dept,
    string $appodate,
    string $scheduletime,
    string $hospitalName = 'E Medicare Health Center'
): bool {
    $mail = new PHPMailer(true);
    try {
        /* Gmail SMTP */
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'musawirkorai@gmail.com';   // <-- change me
        $mail->Password   = 'ytlagztqaplybzdc';       // <-- change me (App Password, NOT login!)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
        $mail->Port       = 587;

        /* Recipients */
        $mail->setFrom('yourgmail@example.com', $hospitalName);
        $mail->addAddress($toEmail, $toName);

        /* Content */
        $mail->isHTML(true);
        $mail->Subject = "Appointment Confirmation  $apponum";
        $mail->Body    = "
            <h2 style=\"margin:0 0 8px\">Appointment Confirmation</h2>
            <p>Dear <strong>$toName</strong>,</p>
            <p>Your appointment has been booked successfully:</p>
            <table cellpadding=\"6\" cellspacing=\"0\" border=\"1\" style=\"border-collapse:collapse\">
              <tr><td><strong>No.</strong></td><td>$apponum</td></tr>
              <tr><td><strong>Doctor</strong></td><td>$docname</td></tr>
              <tr><td><strong>Department</strong></td><td>$dept</td></tr>
              <tr><td><strong>Date</strong></td><td>$appodate</td></tr>
              <tr><td><strong>Time</strong></td><td>$scheduletime</td></tr>
            </table>
            <p>Please arrive 15 min early and bring any previous reports.</p>
            <p>Regards,<br>$hospitalName</p>
        ";
        $mail->AltBody = "Appointment #$apponum\nDoctor: $docname\nDate: $appodate\nTime: $scheduletime";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/* ---------- 4.  Handle the POST request from the "Book Appointment" button ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booknow'])) {
    /* 4‑a.  Sanitize & collect form data */
    $apponum    = (string) $_POST['apponum'];        // e.g. APPT‑123
    $scheduleid = (int)    $_POST['scheduleid'];
    $date       = $_POST['date'];                    // expected Y‑m‑d

    /* 4‑b.  Fetch patient id + name */
    $stmt = $database->prepare('SELECT pid, pname FROM patient WHERE pemail = ? LIMIT 1');
    $stmt->bind_param('s', $useremail);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $pid      = $patient['pid'];
    $pname    = $patient['pname'];
    $stmt->close();

    /* 4‑c.  Insert appointment */
    $stmt = $database->prepare(
        'INSERT INTO appointment (pid, apponum, scheduleid, appodate)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isis', $pid, $apponum, $scheduleid, $date);
    $stmt->execute();
    $stmt->close();

    /* 4‑d.  Get doctor & schedule details for the email */
   $sql = "
    SELECT
        d.docname,
        sp.sname AS dept,
        DATE_FORMAT(s.scheduledate, '%Y-%m-%d') AS appodate,
        DATE_FORMAT(s.scheduletime, '%h:%i %p') AS scheduletime
    FROM schedule s
    JOIN doctor d ON d.docid = s.docid
    JOIN specialties sp ON d.specialties = sp.id
    WHERE s.scheduleid = ?
    LIMIT 1
";

    $stmt = $database->prepare($sql);
    $stmt->bind_param('i', $scheduleid);
    $stmt->execute();
    $sched = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    /* Fallbacks in case nothing returned (avoid undefined index) */
    $docname      = $sched['docname']      ?? 'Doctor';
    $dept         = $sched['dept']         ?? 'Department';
    $appodate     = $sched['appodate']     ?? $date;
    $scheduletime = $sched['scheduletime'] ?? '-';

    /* 4‑e.  Send confirmation email */
    if (sendAppointmentEmail(
            $useremail,
            $pname,
            $apponum,
            $docname,
            $dept,
            $appodate,
            $scheduletime
        )) {
        /* 4‑f.  Redirect once everything succeeded */
        header("Location: appointment.php?action=booking-added&id=$apponum");
        exit;
    }

    /* 4‑g.  Mail failed → show message */
    echo '<p style="color:red;margin:20px 0">❌  Appointment saved, but confirmation email failed.</p>';
}
?>
