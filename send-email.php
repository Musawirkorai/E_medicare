<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendAppointmentEmail($useremail, $username, $apponum, $docname, $title, $appodate, $scheduletime, $hospitalName="E-Medicare Health Center") {
    $mail = new PHPMailer(true);

    try {
        // SMTP Config
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'musawirkorai@gmail.com';
        $mail->Password   = 'ytlagztqaplybzdc';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Sender & Recipient
        $mail->setFrom('Medicare@health.com', $hospitalName);
        $mail->addAddress($useremail, $username);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Appointment Confirmation - {$apponum}";
        $mail->Body = "
            <h2>Appointment Confirmation</h2>
            <p>Dear <strong>{$username}</strong>,</p>
            <p>Your appointment has been successfully booked:</p>
            <table>
                <tr><td><strong>Appointment Number:</strong></td><td>{$apponum}</td></tr>
                <tr><td><strong>Doctor:</strong></td><td>{$docname}</td></tr>
                <tr><td><strong>Department:</strong></td><td>{$title}</td></tr>
                <tr><td><strong>Date:</strong></td><td>{$appodate}</td></tr>
                <tr><td><strong>Time:</strong></td><td>{$scheduletime}</td></tr>
            </table>
            <p>Please reach 15 minutes before the meantioned time and bring your previous medical record
            <p>Regards,<br>{$hospitalName}</p>
        ";

        $mail->AltBody = "Appointment Number: {$apponum}\nDoctor: {$docname}\nDate: {$appodate}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mailer Error: {$mail->ErrorInfo}";
    }
}
