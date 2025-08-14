<?php
// profile.php

include_once 'functions.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user data from session or database
$user = $_SESSION['user']; // Assuming user data is stored in session after login

// Optional: Fetch fresh data from DB if needed
// $user = getUserById($_SESSION['user']['id']);

function getUserIcon($type)
{
    if ($type === 'admin') {
        return '<span style="font-size:40px;color:#1e90ff;">&#128081;</span>'; // Crown icon for admin
    } else {
        return '<span style="font-size:40px;color:#27ae60;">&#128100;</span>'; // User icon for voter
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .profile-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .profile-header {
            margin-bottom: 20px;
        }

        .profile-info {
            margin: 20px 0;
            text-align: left;
        }

        .profile-info i {
            color: #27ae60;
            margin-right: 10px;
        }

        .profile-links a {
            margin: 0 10px;
            color: #1e90ff;
            text-decoration: none;
            font-weight: bold;
        }

        .profile-links a:hover {
            text-decoration: underline;
        }

        .logout-btn {
            margin-top: 25px;
            padding: 8px 18px;
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            background: #27ae60;
            color: #fff;
            font-size: 13px;
            margin-left: 8px;
        }

        .role-badge.admin {
            background: #1e90ff;
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <div class="profile-header">
            <?php echo getUserIcon($user['user_type']); ?>
            <h2>
                <?php echo htmlspecialchars($user['username']); ?>
                <span class="role-badge <?php echo $user['user_type'] === 'admin' ? 'admin' : ''; ?>">
                    <?php echo ucfirst($user['user_type']); ?>
                </span>
            </h2>
        </div>
        <div class="profile-info">
            <p><i class="fa fa-id-card"></i> <strong>National ID:</strong> <?php echo htmlspecialchars($user['ID']); ?></p>
            <p><i class="fa fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <div class="profile-links">
            <a href="index.php"><i class="fa fa-home"></i> Home</a>
            <?php if ($user['user_type'] === 'admin'): ?>
                <a href="admin_dashboard.php"><i class="fa fa-cogs"></i> Admin Dashboard</a>
            <?php else: ?>
                <a href="vote.php"><i class="fa fa-vote-yea"></i> Vote Now</a>
            <?php endif; ?>
            <a href="edit_profile.php"><i class="fa fa-user-edit"></i> Edit Profile</a>
        </div>

        <form method="post" action="logout.php">
            <button type="submit" class="logout-btn">
                <a class="fa fa-sign-out-alt" href="index.php?logout='1'">Logout</a>
            </button>

        </form>
    </div>
</body>

</html>