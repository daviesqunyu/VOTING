<?php
include_once 'functions.php';

// If user is already logged in, redirect to main page
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | E-Voting System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: #666;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .user-type-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .user-type-section p {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .btn-register {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-register:hover {
            background: #0056b3;
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #007bff;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .logo {
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Create Account</h2>
            <p>Join the E-Voting System</p>
        </div>

        <form method="post" action="register.php">
            <!-- Display validation errors -->
            <?php echo display_error(); ?>

            <!-- User Type Selection -->
            <div class="user-type-section">
                <p>Select your user type:</p>
                <div class="form-group">
                    <select id="user_type" name="user_type" required>
                        <option value="" disabled selected>–– Choose User Type ––</option>
                        <option value="user">Voter</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <!-- Full Name -->
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Full Name
                </label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                       required placeholder="Enter your full name">
            </div>

            <!-- National ID -->
            <div class="form-group">
                <label for="ID">
                    <i class="fas fa-id-card"></i> National ID
                </label>
                <input type="text" id="ID" name="ID" 
                       value="<?php echo htmlspecialchars($ID ?? ''); ?>" 
                       required placeholder="Enter your national ID">
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                       required placeholder="Enter your email address">
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password_1">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password_1" name="password_1" 
                       required placeholder="Enter your password">
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="password_2">
                    <i class="fas fa-lock"></i> Confirm Password
                </label>
                <input type="password" id="password_2" name="password_2" 
                       required placeholder="Confirm your password">
            </div>

            <!-- Register Button -->
            <button type="submit" class="btn-register" name="register_btn">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <div class="links">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>

    <script>
        // Simple user type selection feedback
        document.getElementById("user_type").addEventListener("change", function() {
            console.log("Selected user type:", this.value);
        });
    </script>
</body>
</html>