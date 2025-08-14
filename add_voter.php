<?php
include('functions.php');

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header('Location: login.php');
    exit();
}

$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($username)) {
        $error = "Username is required";
    } elseif (empty($national_id)) {
        $error = "National ID is required";
    } elseif (empty($email)) {
        $error = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (empty($password)) {
        $error = "Password is required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if user already exists
        $check_sql = "SELECT * FROM users WHERE username = ? OR ID = ? OR email = ?";
        $stmt = $db->prepare($check_sql);
        $stmt->bind_param("sss", $username, $national_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "User with this username, national ID, or email already exists";
        } else {
            // Add new voter
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (username, ID, email, password, user_type) VALUES (?, ?, ?, ?, 'user')";
            $insert_stmt = $db->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $username, $national_id, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success = "Voter added successfully!";
                // Clear form data
                $username = $national_id = $email = "";
            } else {
                $error = "Failed to add voter: " . $db->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Voter | E-Voting System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn {
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
        
        .btn:hover {
            background: #0056b3;
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .nav-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .nav-links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Add New Voter</h1>
            <p>Register a new voter in the system</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="add_voter.php">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Full Name
                </label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                       required placeholder="Enter full name">
            </div>

            <div class="form-group">
                <label for="national_id">
                    <i class="fas fa-id-card"></i> National ID
                </label>
                <input type="text" id="national_id" name="national_id" 
                       value="<?php echo htmlspecialchars($national_id ?? ''); ?>" 
                       required placeholder="Enter national ID">
            </div>

            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address
                </label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                       required placeholder="Enter email address">
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" 
                       required placeholder="Enter password (min 6 characters)">
            </div>

            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i> Confirm Password
                </label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       required placeholder="Confirm password">
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Add Voter
            </button>
        </form>

        <div class="nav-links">
            <a href="admin_dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="view_voter.php">
                <i class="fas fa-users"></i> View All Voters
            </a>
        </div>
    </div>
</body>
</html>