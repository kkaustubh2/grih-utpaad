<!DOCTYPE html>
<html>
<head>
    <title>Register - Grih Utpaad</title>
    <link rel="stylesheet" href="assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 20px 25px;
            font-size: 1.4rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            height: auto;
            line-height: 1.5;
        }
        .form-control:focus {
            border-color: #007B5E;
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 123, 94, 0.1);
        }
        .btn {
            width: 100%;
            padding: 20px;
            font-size: 1.4rem;
            font-weight: 500;
            margin-top: 10px;
        }
        .card {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
        }
        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .card-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-size: 2.2rem;
        }
        .alert {
            margin-bottom: 20px;
            padding: 15px 20px;
            font-size: 1.1rem;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
        body {
            background-color: #f8f9fa;
        }
        .container {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Register</h2>
            </div>
            <div class="card-body">
                <form action="register.php" method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn">Register</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 