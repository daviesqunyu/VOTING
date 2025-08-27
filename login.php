<?php
// Include shared logic
include('functions.php');

// If user is already logged in, send them to the main page
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | E-Voting System</title>
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
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #3807ffff;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #10f869ff;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn-login {
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
        
        .btn-login:hover {
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
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 10px;
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
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
            </div>
            <h2>Welcome Back</h2>
            <p>Sign in to your E-Voting account</p>
        </div>

        <form method="post" action="login.php">
            <!-- Display session messages -->
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['msg']); ?>
                    <?php unset($_SESSION['msg']); ?>
                </div>
            <?php endif; ?>

            <!-- Display success message -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Display validation errors -->
            <?php echo display_error(); ?>

            <!-- Username input -->
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Full Name
                </label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                       required placeholder="Enter your full name">
            </div>

            <!-- National ID input -->
            <div class="form-group">
                <label for="ID">
                    <i class="fas fa-id-card"></i> National ID
                </label>
                <input type="text" id="ID" name="ID" 
                       value="<?php echo htmlspecialchars($ID ?? ''); ?>" 
                       required placeholder="Enter your national ID">
            </div>

            <!-- Password input -->
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" 
                       required placeholder="Enter your password">
            </div>

            <!-- Submit button -->
            <button type="submit" class="btn-login" name="login_btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="links">
            <a href="forgot_password.php">
                <i class="fas fa-key"></i> Forgot Password?
            </a>
            <br><br>
            <a href="register.php">
                <i class="fas fa-user-plus"></i> Don't have an account? Register here
            </a>
        </div>
    </div>
</body>
</html>