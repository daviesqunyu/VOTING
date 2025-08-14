<?php
session_start();

// Include database connection and functions
require_once 'functions.php';

// Initialize variables
$error = '';
$success = '';

// Check if email and token are provided in the URL
if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
    $token = $_GET['token'];

    if ($email === false) {
        $error = "Invalid email address.";
    } else {
        // Check if reset_token column exists, if not create it
        $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
        if ($check_column->num_rows == 0) {
            $db->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL, ADD COLUMN reset_token_expiry DATETIME NULL");
        }

        // Verify the token against the database and check expiry
        $sql = "SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expiry > NOW()";
        $stmt = $db->prepare($sql);

        if ($stmt === false) {
            $error = "Database error: " . $db->error;
        } else {
            $stmt->bind_param("ss", $email, $token);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_id);
                $stmt->fetch();
                $stmt->close();
                
                // Token is valid, show the password reset form
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Reset Password - E-Voting System</title>
                    <link rel="stylesheet" href="css/style.css">
                    <style>
                        .reset-password-container {
                            max-width: 400px;
                            margin: 50px auto;
                            padding: 30px;
                            background: white;
                            border-radius: 10px;
                            box-shadow: 0 0 20px rgba(0,0,0,0.1);
                        }
                        .reset-password-container h2 {
                            text-align: center;
                            color: #333;
                            margin-bottom: 30px;
                        }
                        .form-group {
                            margin-bottom: 20px;
                        }
                        .form-group label {
                            display: block;
                            margin-bottom: 5px;
                            color: #555;
                            font-weight: 500;
                        }
                        .form-group input {
                            width: 100%;
                            padding: 12px;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                            font-size: 16px;
                            box-sizing: border-box;
                        }
                        .form-group input:focus {
                            outline: none;
                            border-color: #007bff;
                            box-shadow: 0 0 5px rgba(0,123,255,0.3);
                        }
                        .btn {
                            width: 100%;
                            padding: 12px;
                            background: #007bff;
                            color: white;
                            border: none;
                            border-radius: 5px;
                            font-size: 16px;
                            cursor: pointer;
                            transition: background 0.3s;
                        }
                        .btn:hover {
                            background: #0056b3;
                        }
                        .alert {
                            padding: 12px;
                            border-radius: 5px;
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
                        .password-requirements {
                            font-size: 12px;
                            color: #666;
                            margin-top: 5px;
                        }
                    </style>
                </head>
                <body>
                    <div class="reset-password-container">
                        <h2>Reset Password</h2>

                        <?php if ($error): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="post" action="reset_password.php" id="resetForm">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="form-group">
                                <label for="password">New Password:</label>
                                <input type="password" id="password" name="password" required minlength="8">
                                <div class="password-requirements">
                                    Password must be at least 8 characters long
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password:</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            </div>

                            <button type="submit" class="btn">Update Password</button>
                        </form>
                    </div>

                    <script>
                        // Client-side password validation
                        document.getElementById('resetForm').addEventListener('submit', function(e) {
                            const password = document.getElementById('password').value;
                            const confirmPassword = document.getElementById('confirm_password').value;
                            
                            if (password !== confirmPassword) {
                                e.preventDefault();
                                alert('Passwords do not match!');
                                return false;
                            }
                            
                            if (password.length < 8) {
                                e.preventDefault();
                                alert('Password must be at least 8 characters long!');
                                return false;
                            }
                        });
                    </script>
                </body>
                </html>
                <?php
                exit();
            } else {
                $error = "Invalid or expired reset link. Please request a new password reset.";
                $stmt->close();
            }
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process the password reset form
    $email = $_POST['email'];
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($email) || empty($token) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if reset_token column exists, if not create it
        $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
        if ($check_column->num_rows == 0) {
            $db->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL, ADD COLUMN reset_token_expiry DATETIME NULL");
        }

        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update the password in the database and clear the reset token
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE email = ? AND reset_token = ? AND reset_token_expiry > NOW()";
        $stmt = $db->prepare($sql);

        if ($stmt === false) {
            $error = "Database error: " . $db->error;
        } else {
            $stmt->bind_param("sss", $hashed_password, $email, $token);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success = "Password updated successfully. Please login with your new password.";
                // Redirect to login page after 3 seconds
                header("refresh:3;url=login.php");
            } else {
                $error = "Invalid or expired reset link. Please request a new password reset.";
            }
            $stmt->close();
        }
    }
} else {
    // Invalid request, redirect to forgot password page
    header("Location: forgot_password.php");
    exit();
}

// If we reach here, there was an error, show the error page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - E-Voting System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .error-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <a href="forgot_password.php" class="btn">Request New Password Reset</a>
    </div>
</body>
</html>