<?php
session_start();
require_once('config/db.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>About Us - GRIH-UTPAAD</title>
    <link rel="stylesheet" href="assets/uploads/css/style.css">
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
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .about-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #007B5E;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .mission-card, .vision-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(0, 123, 94, 0.1);
        }

        .stat-value {
            font-size: 2em;
            color: #007B5E;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        h1, h2, h3 {
            color: #2c3e50;
        }

        .icon-feature {
            font-size: 2em;
            color: #007B5E;
            margin-bottom: 15px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007B5E;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .profile-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #007B5E;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            border: 3px solid #007B5E;
            box-shadow: 0 0 20px rgba(0, 123, 94, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="about-section">
            <h1><i class="fas fa-home"></i> About GRIH-UTPAAD</h1>
            <p>GRIH-UTPAAD is a pioneering e-commerce platform dedicated to empowering female householders by providing them with opportunities to showcase and sell their homemade products. Our platform bridges the gap between talented home-based entrepreneurs and consumers looking for authentic, handcrafted items.</p>
        </div>

        <div class="mission-vision">
            <div class="mission-card">
                <h2><i class="fas fa-bullseye"></i> Our Mission</h2>
                <p>To empower female householders by providing them with a digital platform to transform their skills into sustainable businesses, while ensuring consumers have access to quality homemade products.</p>
            </div>
            <div class="vision-card">
                <h2><i class="fas fa-eye"></i> Our Vision</h2>
                <p>To become the leading marketplace for homemade products in India, creating economic independence for thousands of female entrepreneurs while preserving and promoting traditional crafts and skills.</p>
            </div>
        </div>

        <div class="stats-grid">
            <?php
            // Fetch platform statistics
            $stats = $conn->query("
                SELECT 
                    COUNT(DISTINCT CASE WHEN role = 'female_householder' THEN id END) as total_sellers,
                    COUNT(DISTINCT CASE WHEN role = 'consumer' THEN id END) as total_consumers,
                    (SELECT COUNT(*) FROM products) as total_products,
                    (SELECT COUNT(*) FROM orders) as total_orders
                FROM users
            ")->fetch_assoc();
            ?>
            <div class="stat-card">
                <i class="fas fa-store icon-feature"></i>
                <div class="stat-value"><?php echo number_format($stats['total_sellers']); ?></div>
                <div class="stat-label">Active Sellers</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users icon-feature"></i>
                <div class="stat-value"><?php echo number_format($stats['total_consumers']); ?></div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-box icon-feature"></i>
                <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                <div class="stat-label">Products Listed</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-shopping-cart icon-feature"></i>
                <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                <div class="stat-label">Orders Completed</div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-hand-holding-heart icon-feature"></i>
                <h3>Quality Assurance</h3>
                <p>Every product undergoes quality checks to ensure the highest standards for our customers.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-rupee-sign icon-feature"></i>
                <h3>Fair Pricing</h3>
                <p>We ensure fair prices for both sellers and buyers, creating a sustainable marketplace.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-truck icon-feature"></i>
                <h3>Secure Delivery</h3>
                <p>Safe and timely delivery of products across India with real-time tracking.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-headset icon-feature"></i>
                <h3>24/7 Support</h3>
                <p>Round-the-clock customer support to assist both sellers and buyers.</p>
            </div>
        </div>

        <!-- Team Section -->
        <div style="margin-top: 60px;">
            <h2 style="text-align: center; color: #2c3e50; margin-bottom: 40px;">
                <i class="fas fa-users"></i> Meet Our Team
            </h2>
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 30px;
                margin-top: 20px;
            ">
                <!-- Kaustubh's Card -->
                <div style="background: white; border-radius: 15px; padding: 30px; text-align: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                    <div class="profile-circle">K</div>
                    <h3 style="color: #2c3e50; margin: 0 0 10px;">Kaustubh</h3>
                    <p style="color: #007B5E; margin: 0 0 15px;">Full Stack Developer</p>
                    <p style="color: #666; font-size: 0.9em; margin: 0 0 20px;">
                        Passionate about creating seamless user experiences and robust backend systems.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="https://github.com/kaustubh" target="_blank" style="
                            color: #2c3e50;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background: rgba(0, 123, 94, 0.1);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.3s;
                        " onmouseover="this.style.background='#007B5E';this.style.color='white'" 
                           onmouseout="this.style.background='rgba(0, 123, 94, 0.1)';this.style.color='#2c3e50'">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://linkedin.com/in/kaustubh" target="_blank" style="
                            color: #2c3e50;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background: rgba(0, 123, 94, 0.1);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.3s;
                        " onmouseover="this.style.background='#007B5E';this.style.color='white'" 
                           onmouseout="this.style.background='rgba(0, 123, 94, 0.1)';this.style.color='#2c3e50'">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <!-- Shivam's Card -->
                <div style="background: white; border-radius: 15px; padding: 30px; text-align: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                    <div class="profile-circle">S</div>
                    <h3 style="color: #2c3e50; margin: 0 0 10px;">Shivam</h3>
                    <p style="color: #007B5E; margin: 0 0 15px;">Backend Developer</p>
                    <p style="color: #666; font-size: 0.9em; margin: 0 0 20px;">
                        Specialized in building scalable backend architectures and database optimization.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="https://github.com/shivam" target="_blank" style="
                            color: #2c3e50;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background: rgba(0, 123, 94, 0.1);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.3s;
                        " onmouseover="this.style.background='#007B5E';this.style.color='white'" 
                           onmouseout="this.style.background='rgba(0, 123, 94, 0.1)';this.style.color='#2c3e50'">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://linkedin.com/in/shivam" target="_blank" style="
                            color: #2c3e50;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background: rgba(0, 123, 94, 0.1);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.3s;
                        " onmouseover="this.style.background='#007B5E';this.style.color='white'" 
                           onmouseout="this.style.background='rgba(0, 123, 94, 0.1)';this.style.color='#2c3e50'">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <!-- Akash's Card -->
                <div style="background: white; border-radius: 15px; padding: 30px; text-align: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                    <div class="profile-circle">A</div>
                    <h3 style="color: #2c3e50; margin: 0 0 10px;">Akash</h3>
                    <p style="color: #007B5E; margin: 0 0 15px;">Frontend Developer</p>
                    <p style="color: #666; font-size: 0.9em; margin: 0 0 20px;">
                        Expert in creating beautiful and responsive user interfaces with modern technologies.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="https://github.com/akash" target="_blank" style="
                            color: #2c3e50;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background: rgba(0, 123, 94, 0.1);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.3s;
                        " onmouseover="this.style.background='#007B5E';this.style.color='white'" 
                           onmouseout="this.style.background='rgba(0, 123, 94, 0.1)';this.style.color='#2c3e50'">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://linkedin.com/in/akash" target="_blank" style="
                            color: #2c3e50;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background: rgba(0, 123, 94, 0.1);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.3s;
                        " onmouseover="this.style.background='#007B5E';this.style.color='white'" 
                           onmouseout="this.style.background='rgba(0, 123, 94, 0.1)';this.style.color='#2c3e50'">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
</body>
</html> 