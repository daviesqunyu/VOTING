<?php
include('functions.php');

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header('Location: login.php');
    exit();
}

$success = $error = "";

// Handle voter removal
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $voter_id = intval($_GET['remove']);
    
    // Check if voter exists and is not an admin
    $check_sql = "SELECT * FROM users WHERE ID = ? AND user_type = 'user'";
    $stmt = $db->prepare($check_sql);
    $stmt->bind_param("i", $voter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $voter = $result->fetch_assoc();
        
        // Delete voter's votes first
        $delete_votes_sql = "DELETE FROM votes WHERE voter_id = ?";
        $votes_stmt = $db->prepare($delete_votes_sql);
        $votes_stmt->bind_param("i", $voter_id);
        $votes_stmt->execute();
        $votes_stmt->close();
        
        // Delete voter
        $delete_sql = "DELETE FROM users WHERE ID = ? AND user_type = 'user'";
        $delete_stmt = $db->prepare($delete_sql);
        $delete_stmt->bind_param("i", $voter_id);
        
        if ($delete_stmt->execute()) {
            $success = "Voter '" . htmlspecialchars($voter['username']) . "' has been removed successfully.";
        } else {
            $error = "Failed to remove voter: " . $db->error;
        }
        $delete_stmt->close();
    } else {
        $error = "Voter not found or cannot be removed.";
    }
    $stmt->close();
}

// Get all voters
$voters = [];
$sql = "SELECT * FROM users WHERE user_type = 'user' ORDER BY username";
$result = mysqli_query($db, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $voters[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Remove Voter | E-Voting System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: #007bff;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px;
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
        
        .voters-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .voters-table th,
        .voters-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .voters-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .voters-table tr:hover {
            background: #f5f5f5;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .no-voters {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-minus"></i> Remove Voters</h1>
            <p>Manage registered voters in the system</p>
        </div>

        <div class="content">
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

            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> Removing a voter will also delete all their voting records. This action cannot be undone.
            </div>

            <?php if (empty($voters)): ?>
                <div class="no-voters">
                    <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No Voters Found</h3>
                    <p>There are no registered voters in the system.</p>
                </div>
            <?php else: ?>
                <table class="voters-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>National ID</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($voters as $voter): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($voter['username']); ?></td>
                                <td><?php echo htmlspecialchars($voter['ID']); ?></td>
                                <td><?php echo htmlspecialchars($voter['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($voter['created_at'] ?? 'now')); ?></td>
                                <td>
                                    <a href="?remove=<?php echo $voter['ID']; ?>" 
                                       class="btn-remove"
                                       onclick="return confirm('Are you sure you want to remove this voter? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <a href="admin_dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>