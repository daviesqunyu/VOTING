<?php
require_once 'functions.php';

// Initialize variables
$error = "";

// Check if user is logged in and is a voter
if (!isLoggedIn()) {
    $_SESSION['error'] = "You must be logged in to view your votes.";
    header('Location: login.php');
    exit;
}
if (isAdmin()) {
    $_SESSION['error'] = "Admins cannot view voter votes. Please use a voter account.";
    header('Location: admin_dashboard.php');
    exit;
}

// Get voter info
$voter_id = isset($_SESSION['user']['ID']) ? intval($_SESSION['user']['ID']) : 0;
$voter_info = $voter_id ? getVoterInfo($voter_id) : null;

// Check if voter has voted and get their votes
$has_voted = false;
$voter_votes = [];
if ($voter_id) {
    $has_voted = hasVoterVoted($voter_id);
    if ($has_voted) {
        $voter_votes = getVoterVotes($voter_id);
    }
}

// Get voting status
$voting_status = getVotingStatus();

// Show results if allowed
$show_results = canShowResults();
$results = [];
if ($show_results) {
    $results = getVotingResults();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Votes - E-Voting System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }
        
        .main-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .voter-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .candidate-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
        }
        
        .card {
            border-radius: 0.75rem;
            box-shadow: 0 2px 16px rgba(102, 126, 234, 0.08);
            border: none;
            margin-bottom: 1rem;
        }
        
        .card-header {
            border-radius: 0.75rem 0.75rem 0 0;
            background: linear-gradient(90deg, #43cea2 0%, #185a9d 100%);
            color: white;
        }
        
        .results-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .progress {
            height: 25px;
            border-radius: 15px;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-vote-yea mr-2"></i>
                E-Voting System
            </a>
            
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="vote.php">
                            <i class="fas fa-vote-yea mr-1"></i> Vote
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_votes.php">
                            <i class="fas fa-eye mr-1"></i> My Votes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="p-4">
                <!-- Voter Information Card -->
                <?php if (isset($voter_info) && $voter_info): ?>
                <div class="voter-info-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4><i class="fas fa-user mr-2"></i>Welcome, <?php echo htmlspecialchars($voter_info['full_name'] ?? 'Voter'); ?></h4>
                            <p class="mb-0">Voter ID: <?php echo htmlspecialchars($voter_info['voter_id'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="badge <?php echo $has_voted ? 'badge-success' : 'badge-warning'; ?> p-2">
                                <i class="fas <?php echo $has_voted ? 'fa-check-circle' : 'fa-clock'; ?> mr-1"></i>
                                <?php echo $has_voted ? 'Voted' : 'Not Voted'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Alerts -->
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); ?>
                        <?php unset($_SESSION['error']); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); ?>
                        <?php unset($_SESSION['success']); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Voting Status -->
                <div class="alert <?php echo $voting_status['is_active'] ? 'alert-success' : 'alert-warning'; ?>">
                    <i class="fas <?php echo $voting_status['is_active'] ? 'fa-play-circle' : 'fa-pause-circle'; ?> mr-2"></i>
                    <strong>Voting Status:</strong> <?php echo htmlspecialchars($voting_status['message']); ?>
                </div>

                <?php if ($has_voted): ?>
                    <!-- Voter's Votes -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-check-circle mr-2"></i>Your Votes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($voter_votes)): ?>
                                <?php foreach ($voter_votes as $vote): ?>
                                    <div class="mb-3 p-3 border rounded">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($vote['image_path'] ?? 'img/default-candidate.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($vote['candidate_name']); ?>" 
                                                 class="candidate-image mr-3">
                                            <div>
                                                <h6 class="text-primary mb-1"><?php echo htmlspecialchars($vote['position_type']); ?></h6>
                                                <strong><?php echo htmlspecialchars($vote['candidate_name']); ?></strong>
                                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($vote['party']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Thank you for voting! Your vote has been securely recorded.
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted">No vote details available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Not Voted Message -->
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        You haven't voted yet. <a href="vote.php" class="alert-link">Click here to vote</a>
                    </div>
                <?php endif; ?>

                <?php if ($show_results): ?>
                    <!-- Results Section -->
                    <div class="results-card">
                        <h3 class="text-center mb-4"><i class="fas fa-chart-bar mr-2"></i>Election Results</h3>
                        <?php if (!empty($results)): ?>
                            <?php foreach ($results as $position => $candidates): ?>
                                <div class="mb-4">
                                    <h5 class="border-bottom pb-2"><?php echo htmlspecialchars($position); ?></h5>
                                    <?php foreach ($candidates as $candidate): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($candidate['name']); ?></strong>
                                                <span class="text-light">(<?php echo htmlspecialchars($candidate['party']); ?>)</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="badge badge-light text-dark mr-2"><?php echo $candidate['votes']; ?> votes</span>
                                                <span><?php echo $candidate['percentage']; ?>%</span>
                                            </div>
                                        </div>
                                        <div class="progress mb-3">
                                            <div class="progress-bar bg-warning" style="width: <?php echo $candidate['percentage']; ?>%"></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center">No results available yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Navigation Links -->
                <div class="text-center mt-4">
                    <a href="vote.php" class="btn btn-primary mr-2">
                        <i class="fas fa-vote-yea mr-1"></i> Go to Voting
                    </a>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-home mr-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>