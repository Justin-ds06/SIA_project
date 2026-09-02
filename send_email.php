<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================================================
   COURT22 - EMAIL SENDER
   ========================================================= */

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.php";


/**
 * Send booking confirmation email
 *
 * @return array
 */
function sendBookingConfirmation(
    string $customerName,
    string $customerEmail,
    int $bookingId,
    string $sport,
    string $court,
    string $bookingDate,
    string $startTime,
    int $duration,
    float $totalFee,
    float $amountPaid,
    float $balance,
    string $paymentMethod
): array {

    global $mail_username;
    global $mail_password;
    global $mail_from_name;

    $mail = new PHPMailer(true);

    try {

        /* =====================================================
           SMTP CONFIGURATION
        ===================================================== */

        $mail->isSMTP();

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;

        $mail->Username = $mail_username;
        $mail->Password = $mail_password;

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        /*
         * Keep this 0 in the real application.
         * Your SMTP test already confirmed Gmail works.
         */
        $mail->SMTPDebug = 0;

        $mail->CharSet = "UTF-8";


        /* =====================================================
           SENDER / RECEIVER
        ===================================================== */

        $mail->setFrom(
            $mail_username,
            $mail_from_name
        );

        $mail->addReplyTo(
            $mail_username,
            $mail_from_name
        );

        $mail->addAddress(
            $customerEmail,
            $customerName
        );


        /* =====================================================
           EMAIL SUBJECT
        ===================================================== */

        $mail->isHTML(true);

        $mail->Subject =
            "Court22 Booking Confirmation #" .
            $bookingId;


        /* =====================================================
           FORMAT DATA
        ===================================================== */

        $formattedDate =
            date(
                "F d, Y",
                strtotime($bookingDate)
            );

        $formattedTime =
            date(
                "h:i A",
                strtotime($startTime)
            );

        $formattedTotal =
            number_format(
                $totalFee,
                2
            );

        $formattedPaid =
            number_format(
                $amountPaid,
                2
            );

        $formattedBalance =
            number_format(
                $balance,
                2
            );


        /* =====================================================
           PAYMENT STATUS
        ===================================================== */

        if ($balance <= 0) {

            $paymentStatus = "PAID";

        } else {

            $paymentStatus = "DOWNPAYMENT";
        }


        /* =====================================================
           HTML EMAIL
        ===================================================== */

        $mail->Body = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Court22 Booking Confirmation</title>

</head>

<body style="
    margin:0;
    padding:0;
    background:#f4f6f8;
    font-family:Arial,Helvetica,sans-serif;
">

<table width="100%"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="background:#f4f6f8;padding:30px 10px;">

<tr>

<td align="center">

<table width="600"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="
           max-width:600px;
           width:100%;
           background:#ffffff;
           border-radius:12px;
           overflow:hidden;
           box-shadow:0 4px 20px rgba(0,0,0,0.08);
       ">

<!-- HEADER -->

<tr>

<td style="
    background:#111827;
    padding:30px;
    text-align:center;
">

<h1 style="
    margin:0;
    color:#ffffff;
    font-size:30px;
    letter-spacing:1px;
">

COURT<span style="color:#f59e0b;">22</span>

</h1>

<p style="
    margin:8px 0 0;
    color:#d1d5db;
    font-size:14px;
">

Court Reservation System

</p>

</td>

</tr>


<!-- CONTENT -->

<tr>

<td style="padding:35px 30px;">

<h2 style="
    margin:0 0 10px;
    color:#111827;
">

Booking Confirmed!

</h2>

<p style="
    margin:0 0 25px;
    color:#4b5563;
    font-size:15px;
    line-height:1.6;
">

Hello <strong>' .
htmlspecialchars($customerName) .
'</strong>,

<br><br>

Your Court22 court reservation has been successfully confirmed.

</p>


<!-- BOOKING ID -->

<table width="100%"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="
           background:#f9fafb;
           border:1px solid #e5e7eb;
           border-radius:8px;
           margin-bottom:25px;
       ">

<tr>

<td style="
    padding:20px;
    text-align:center;
">

<p style="
    margin:0 0 5px;
    color:#6b7280;
    font-size:12px;
    text-transform:uppercase;
">

Booking ID

</p>

<p style="
    margin:0;
    color:#111827;
    font-size:24px;
    font-weight:bold;
">

#' . $bookingId . '

</p>

</td>

</tr>

</table>


<!-- BOOKING DETAILS -->

<h3 style="
    color:#111827;
    margin:0 0 15px;
">

Booking Details

</h3>

<table width="100%"
       cellpadding="8"
       cellspacing="0"
       border="0"
       style="
           border-collapse:collapse;
           font-size:14px;
       ">

<tr>

<td style="
    color:#6b7280;
    border-bottom:1px solid #eeeeee;
">

Sport

</td>

<td align="right"
    style="
        color:#111827;
        font-weight:bold;
        border-bottom:1px solid #eeeeee;
    ">

' . htmlspecialchars($sport) . '

</td>

</tr>


<tr>

<td style="
    color:#6b7280;
    border-bottom:1px solid #eeeeee;
">

Court

</td>

<td align="right"
    style="
        color:#111827;
        font-weight:bold;
        border-bottom:1px solid #eeeeee;
    ">

' . htmlspecialchars($court) . '

</td>

</tr>


<tr>

<td style="
    color:#6b7280;
    border-bottom:1px solid #eeeeee;
">

Date

</td>

<td align="right"
    style="
        color:#111827;
        font-weight:bold;
        border-bottom:1px solid #eeeeee;
    ">

' . $formattedDate . '

</td>

</tr>


<tr>

<td style="
    color:#6b7280;
    border-bottom:1px solid #eeeeee;
">

Start Time

</td>

<td align="right"
    style="
        color:#111827;
        font-weight:bold;
        border-bottom:1px solid #eeeeee;
    ">

' . $formattedTime . '

</td>

</tr>


<tr>

<td style="
    color:#6b7280;
    border-bottom:1px solid #eeeeee;
">

Duration

</td>

<td align="right"
    style="
        color:#111827;
        font-weight:bold;
        border-bottom:1px solid #eeeeee;
    ">

' . $duration . ' hour' .
($duration > 1 ? 's' : '') . '

</td>

</tr>


<tr>

<td style="
    color:#6b7280;
">

Payment Method

</td>

<td align="right"
    style="
        color:#111827;
        font-weight:bold;
    ">

' . htmlspecialchars($paymentMethod) . '

</td>

</tr>

</table>


<!-- PAYMENT -->

<h3 style="
    color:#111827;
    margin:30px 0 15px;
">

Payment Summary

</h3>

<table width="100%"
       cellpadding="8"
       cellspacing="0"
       border="0"
       style="
           border-collapse:collapse;
           font-size:14px;
       ">

<tr>

<td style="color:#6b7280;">

Total Fee

</td>

<td align="right"
    style="
        color:#111827;
        font-weight:bold;
    ">

₱' . $formattedTotal . '

</td>

</tr>


<tr>

<td style="color:#6b7280;">

Amount Paid

</td>

<td align="right"
    style="
        color:#16a34a;
        font-weight:bold;
    ">

₱' . $formattedPaid . '

</td>

</tr>


<tr>

<td style="color:#6b7280;">

Remaining Balance

</td>

<td align="right"
    style="
        color:#dc2626;
        font-weight:bold;
    ">

₱' . $formattedBalance . '

</td>

</tr>

</table>


<!-- STATUS -->

<div style="
    margin-top:25px;
    padding:15px;
    background:#ecfdf5;
    border:1px solid #a7f3d0;
    border-radius:8px;
    text-align:center;
">

<p style="
    margin:0;
    color:#047857;
    font-size:14px;
    font-weight:bold;
">

✓ BOOKING CONFIRMED — ' .
$paymentStatus .
'

</p>

</div>


<p style="
    margin:30px 0 0;
    color:#6b7280;
    font-size:13px;
    line-height:1.6;
">

Please keep this email for your records.

If you have any questions regarding your reservation,
please contact the Court22 administration.

</p>

</td>

</tr>


<!-- FOOTER -->

<tr>

<td style="
    background:#111827;
    padding:20px;
    text-align:center;
">

<p style="
    margin:0;
    color:#ffffff;
    font-size:13px;
">

© ' . date("Y") . ' Court22

</p>

<p style="
    margin:6px 0 0;
    color:#9ca3af;
    font-size:11px;
">

Court Reservation System

</p>

</td>

</tr>

</table>

</td>

</tr>

</table>

</body>

</html>
';


        /* =====================================================
           PLAIN TEXT VERSION
        ===================================================== */

        $mail->AltBody =
            "COURT22 BOOKING CONFIRMATION\n\n" .

            "Hello " .
            $customerName .
            ",\n\n" .

            "Your court booking has been confirmed.\n\n" .

            "Booking ID: #" .
            $bookingId .
            "\n" .

            "Sport: " .
            $sport .
            "\n" .

            "Court: " .
            $court .
            "\n" .

            "Date: " .
            $formattedDate .
            "\n" .

            "Start Time: " .
            $formattedTime .
            "\n" .

            "Duration: " .
            $duration .
            " hour(s)\n" .

            "Payment Method: " .
            $paymentMethod .
            "\n\n" .

            "Total Fee: ₱" .
            $formattedTotal .
            "\n" .

            "Amount Paid: ₱" .
            $formattedPaid .
            "\n" .

            "Remaining Balance: ₱" .
            $formattedBalance .
            "\n\n" .

            "Status: CONFIRMED\n\n" .

            "Thank you for booking with Court22.";


        /* =====================================================
           SEND
        ===================================================== */

        $mail->send();

        return [
            "success" => true,
            "error"   => ""
        ];

    } catch (Exception $e) {

        error_log(
            "Court22 email error: " .
            $mail->ErrorInfo
        );

        return [
            "success" => false,
            "error"   => $mail->ErrorInfo
        ];
    }
}
?>