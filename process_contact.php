<?php
session_start();
require_once('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    // Email content
    $to = "support@grihutpaad.com"; // Replace with your email
    $email_subject = "New Contact Form Message: $subject";
    $email_body = "You have received a new message from Grih Utpaad contact form.\n\n".
                  "Name: $name\n".
                  "Email: $email\n".
                  "Subject: $subject\n\n".
                  "Message:\n$message";
    $headers = "From: $email\n";
    $headers .= "Reply-To: $email";

    if(mail($to, $email_subject, $email_body, $headers)) {
        $_SESSION['success'] = "Thank you for your message! We'll get back to you soon.";
    } else {
        $_SESSION['error'] = "Sorry, there was an error sending your message. Please try again.";
    }

    header("Location: contact.php");
    exit();
}

header("Location: contact.php");
exit(); 