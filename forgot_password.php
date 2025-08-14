<?php
session_start();

// Include database connection and functions
require_once 'functions.php';

// Function to generate a unique token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Include email configuration
require_once 'email_config.php';

// Function to send email (you can replace this with PHPMailer or other email library)
function sendPasswordResetEmail($email, $reset_link) {
    // Get username for the email template
    global $db;
    $username = 'User'; // Default value
    
    $stmt = $db->prepare("SELECT username FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($username);
        $stmt->fetch();
        $stmt->close();
    }
    
    $subject = "Password Reset Request";
    $message = getPasswordResetEmailTemplate($username, $reset_link);
    
    return sendSimpleEmail($email, $subject, $message);
}

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    $email = filter_var($_POST["email"], FILTER_VALIDATE_EMAIL);

    if ($email === false) {
        $error = "Invalid email address.";
    } else {
        // Check if user exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $db->prepare($sql);

        if ($stmt === false) {
            $error = "Database error: " . $db->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_id);
                $stmt->fetch();

                // Generate a unique token
                $token = generateToken();
                $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // First, check if reset_token column exists, if not create it
                $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
                if ($check_column->num_rows == 0) {
                    $db->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL, ADD COLUMN reset_token_expiry DATETIME NULL");
                }

                // Store the token in the database
                $insert_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
                $insert_stmt = $db->prepare($insert_sql);

                if ($insert_stmt === false) {
                    $error = "Database error: " . $db->error;
                } else {
                    $insert_stmt->bind_param("ssi", $token, $token_expiry, $user_id);
                    $insert_stmt->execute();

                    // Create reset link
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?email=" . urlencode($email) . "&token=" . $token;
                    
                    // Send password reset email
                    if (sendPasswordResetEmail($email, $reset_link)) {
                        $success = "A password reset link has been sent to your email address. Please check your inbox.";
                    } else {
                        $error = "Failed to send email. Please try again or contact support.";
                    }
                }
                $insert_stmt->close();
            } else {
                $error = "No user found with that email address.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - E-Voting System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .forgot-password-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .forgot-password-container h2 {
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
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <h2>Forgot Password</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" action="forgot_password.php">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email address">
            </div>

            <button type="submit" class="btn">Reset Password</button>
        </form>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>