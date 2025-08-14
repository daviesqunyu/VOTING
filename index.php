<?php
include('functions.php');

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['msg'] = "Please log in first";
    header('location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard | E-Voting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="https://img.icons8.com/ios-filled/50/ffffff/laptop.png" alt="Logo" 
                     style="width: 30px; height: 30px; margin-right: 10px;">
                <span class="text-uppercase font-weight-bold">E-Voting System</span>
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <span class="navbar-text text-white mr-3">
                            Welcome, <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong> 
                            (<?php echo ucfirst($_SESSION['user']['user_type']); ?>)
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="text-center py-5 bg-primary" style="margin-top: 76px;">
        <div class="container">
            <h1 class="display-4 text-white">Secure, Transparent & Fast</h1>
            <p class="lead text-white">Empowering citizens through digital democracy.</p>
        </div>
    </header>

    <!-- Dashboard Options -->
    <div class="container py-5">
        <div class="row text-center">
            <!-- Profile Card -->
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <i class="fas fa-user fa-4x mb-3 text-primary"></i>
                        <h5 class="card-title">View Profile</h5>
                        <p class="card-text">See and manage your voter details.</p>
                        <a href="profile.php" class="btn btn-outline-primary">Go to Profile</a>
                    </div>
                </div>
            </div>

            <!-- Vote Card -->
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body" style="background-color: #fff3cd;">
                        <i class="fas fa-vote-yea fa-4x mb-3 text-success"></i>
                        <h5 class="card-title">Cast Vote</h5>
                        <p class="card-text">Make your choice in a secure voting environment.</p>
                        <a href="vote.php" class="btn btn-outline-success">Vote Now</a>
                    </div>
                </div>
            </div>

            <!-- History Card -->
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <i class="fas fa-clock fa-4x mb-3 text-warning"></i>
                        <h5 class="card-title">Voting History</h5>
                        <p class="card-text">Review all your past votes.</p>
                        <a href="history.php" class="btn btn-outline-warning">View History</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <main>
        <!-- Welcome Banner -->
        <section class="py-5" style="background: linear-gradient(135deg, #007bff, #0056b3); color: white;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="font-weight-bold">Welcome to Kenya's Online E-Voting System</h1>
                        <p class="lead">
                            Experience secure, fast, and transparent elections. Our platform uses modern technology 
                            to ensure every vote counts and every voice is heard. Join us in shaping a better future for Kenya.
                        </p>
                        
                        <ul class="list-unstyled mt-3">
                            <li><i class="fas fa-check-circle mr-2"></i> Secure authentication system</li>
                            <li><i class="fas fa-check-circle mr-2"></i> Real-time fraud detection</li>
                            <li><i class="fas fa-check-circle mr-2"></i> Transparent and tamper-proof results</li>
                        </ul>
                        
                        <div class="mt-4">
                            <a href="register.php" class="btn btn-light btn-lg mr-2">
                                <i class="fas fa-user-plus mr-2"></i>Register to Vote
                            </a>
                            <a href="about.html" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-info-circle mr-2"></i>Learn More
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6 text-center">
                        <img src="https://img.icons8.com/ios-filled/150/ffffff/ballot.png" alt="Voting" class="img-fluid">
                        <div class="mt-3">
                            <p><strong>Next Election:</strong> August 9, 2027</p>
                            <p><strong>Time:</strong> 6:00 AM - 6:00 PM</p>
                        </div>
                        <a href="vote.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-tie mr-2"></i>Vote Now
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Voting Section -->
        <div class="container py-5">
            <h1 class="text-center font-weight-bold mb-4" style="color: #0056b3;">Online Voting System</h1>
            <p class="text-center lead mb-5" style="color: #333;">
                Participate in a secure and transparent voting process. Your voice matters, and every vote counts! 
                Join millions of Kenyans in shaping the future of our nation.
            </p>

            <div class="text-center">
                <a href="register.php" class="btn btn-primary btn-lg mx-2 mb-2">
                    <i class="fas fa-user-plus mr-2"></i>Register to Vote
                </a>
                <a href="view_candidates.php" class="btn btn-outline-primary btn-lg mx-2 mb-2">
                    <i class="fas fa-user-tie mr-2"></i>View Candidates
                </a>
                <a href="results.php" class="btn btn-success btn-lg mx-2 mb-2">
                    <i class="fas fa-chart-bar mr-2"></i>View Results
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <p>&copy; 2025 E-Voting System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>