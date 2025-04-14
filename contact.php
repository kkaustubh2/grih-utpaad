<?php
session_start();
require_once('config/db.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Us - Grih Utpaad</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            background-image: url('assets/images/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            z-index: 1;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
            position: relative;
            z-index: 2;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .contact-info {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(0, 123, 94, 0.05);
            border-radius: 10px;
        }

        .info-item i {
            font-size: 24px;
            color: #007B5E;
            margin-right: 15px;
            width: 40px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #007B5E;
            outline: none;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: #007B5E;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn:hover {
            background: #005b46;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0, 123, 94, 0.1);
            color: #007B5E;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #007B5E;
            color: white;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="color: #007B5E; font-size: 36px; margin-bottom: 15px;">Contact Us</h1>
        <p style="color: #2c3e50; font-size: 18px; max-width: 600px; margin: 0 auto;">
            Have questions about Grih Utpaad? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
        </p>
    </div>

    <div class="contact-grid">
        <div class="contact-info">
            <h2 style="color: #2c3e50; margin-bottom: 25px;">Get in Touch</h2>
            
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h3 style="margin: 0 0 5px; color: #2c3e50;">Location</h3>
                    <p style="margin: 0; color: #666;">123 Grih Utpaad Street, Mumbai, India</p>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h3 style="margin: 0 0 5px; color: #2c3e50;">Email</h3>
                    <p style="margin: 0; color: #666;">support@grihutpaad.com</p>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-phone"></i>
                <div>
                    <h3 style="margin: 0 0 5px; color: #2c3e50;">Phone</h3>
                    <p style="margin: 0; color: #666;">+91 123 456 7890</p>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div>
                    <h3 style="margin: 0 0 5px; color: #2c3e50;">Working Hours</h3>
                    <p style="margin: 0; color: #666;">Monday - Friday: 9:00 AM - 6:00 PM</p>
                </div>
            </div>

            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>

        <div class="contact-form">
            <h2 style="color: #2c3e50; margin-bottom: 25px;">Send us a Message</h2>
            
            <form action="process_contact.php" method="POST">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

</body>
</html> 