<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Use Composer autoloader instead of manual includes
require_once __DIR__ . '/../vendor/autoload.php';

function sendEmail($from_name, $from_email, $to_email, $subject, $message)
{
    $mail = new PHPMailer();
    $mail->isSMTP(); 
    $mail->CharSet = 'UTF-8';                        // Send using SMTP
    $mail->SMTPDebug = 0;
    $mail->SMTPSecure = 'ssl';
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'axelangegomez2004@gmail.com';  //SMTP username
    $mail->Password   = 'yxxhpqgfxiulawhd';  //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    
    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($to_email);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $message;
    $mail->setLanguage('fr');
    if (!$mail->send()) {
        return false;
    } else {
        return true;
    }
}