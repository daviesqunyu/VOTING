<?php
require_once 'functions.php';

// Initialize variables
$error = "";

// Check if user is logged in and is a voter
if (!isLoggedIn()) {
    $_SESSION['error'] = "You must be logged in to vote.";
    header('Location: login.php');
    exit;
}
if (isAdmin()) {
    $_SESSION['error'] = "Admins cannot vote. Please use a voter account.";
    header('Location: admin_dashboard.php');
    exit;
}

// Get voting status
$voting_status = getVotingStatus();
if (!$voting_status['is_active']) {
    $error = $voting_status['message'];
}

// Get voter info
$voter_id = isset($_SESSION['user']['ID']) ? intval($_SESSION['user']['ID']) : 0;
$voter_info = $voter_id ? getVoterInfo($voter_id) : null;

// Check if voter has already voted
$has_voted = false;
$voter_votes = [];
if ($voter_id) {
    $has_voted = hasVoterVoted($voter_id);
    if ($has_voted) {
        $voter_votes = getVoterVotes($voter_id);
    }
}

// Get candidates grouped by position
$candidates_by_position = getCandidatesByPosition();

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_votes'])) {
    if ($has_voted) {
        $error = "You have already voted. Multiple voting is not allowed.";
    } elseif (!$voting_status['is_active']) {
        $error = "Voting is currently not active.";
    } else {
        $votes = $_POST['votes'] ?? [];
        if (empty($votes) || !is_array($votes)) {
            $error = "Please select at least one candidate to vote for.";
        } else {
            $valid = true;
            foreach ($votes as $position => $candidate_id) {
                if (!getCandidateById($candidate_id)) {
                    $valid = false;
                    break;
                }
            }
            if (!$valid) {
                $error = "Invalid candidate selection.";
            } elseif (empty($error)) {
                // Extract only candidate IDs for submitVotes
                $candidate_ids = array_values($votes);
                if (submitVotes($voter_id, $candidate_ids)) {
                    // Get transaction IDs from session
                    $transaction_ids = isset($_SESSION['last_vote_transactions']) ? $_SESSION['last_vote_transactions'] : [];
                    $transaction_message = "";
                    if (!empty($transaction_ids)) {
                        $transaction_message = " Blockchain Transaction ID(s): " . implode(", ", array_map(function($id) {
                            return substr($id, 0, 16) . "...";
                        }, $transaction_ids));
                    }
                    $_SESSION['success'] = "Your votes have been successfully submitted and recorded on the blockchain!" . $transaction_message;
                    unset($_SESSION['last_vote_transactions']);
                    header('Location: vote.php');
                    exit;
                } else {
                    $error = "Failed to submit votes. Please try again.";
                }
            }
        }
    }
}

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
    <title>Vote - E-Voting System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }
        
        .candidate-card {
            border: 2px solid #e9ecef;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 1rem;
            background: #fff;
        }
        
        .candidate-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
        }
        
        .candidate-card.selected {
            border-color: #198754;
            background-color: #f8fff9;
            box-shadow: 0 0.25rem 0.75rem rgba(25, 135, 84, 0.2);
        }
        
        .candidate-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
        }
        
        .position-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #0d6efd;
        }
        
        .voted-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #198754;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
        }
        
        .voting-disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .results-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .progress {
            height: 25px;
            border-radius: 15px;
        }
        
        .voter-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .main-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-top: 20px;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="my_votes.php">
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
                            <div class="mt-2">
                                <small class="badge badge-info">
                                    <i class="fas fa-link mr-1"></i>Blockchain Secured
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Alerts -->
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); ?>
                        <?php unset($_SESSION['success']); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
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

                <?php if (!$has_voted && $voting_status['is_active']): ?>
                    <!-- Voting Form -->
                    <form method="post" action="vote.php">
                        <?php if (!empty($candidates_by_position)): ?>
                            <?php foreach ($candidates_by_position as $position => $candidates): ?>
                                <div class="position-section">
                                    <h4 class="mb-3">
                                        <i class="fas fa-user-tie mr-2"></i><?php echo htmlspecialchars($position); ?>
                                    </h4>
                                    <div class="row">
                                        <?php foreach ($candidates as $candidate): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="candidate-card p-3" onclick="selectCandidate(this, '<?php echo $position; ?>', <?php echo $candidate['id']; ?>)">
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars($candidate['image_path'] ?? 'img/default-candidate.jpg'); ?>" 
                                                             alt="<?php echo htmlspecialchars($candidate['name']); ?>" 
                                                             class="candidate-image mr-3">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($candidate['name']); ?></h6>
                                                            <small class="text-muted"><?php echo htmlspecialchars($candidate['party']); ?></small>
                                                        </div>
                                                    </div>
                                                    <input type="radio" name="votes[<?php echo $position; ?>]" 
                                                           value="<?php echo $candidate['id']; ?>" 
                                                           style="display: none;" required>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-4">
                                <button type="submit" name="submit_votes" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Votes
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle mr-2"></i>No candidates available for voting at this time.
                            </div>
                        <?php endif; ?>
                    </form>
                <?php elseif ($has_voted): ?>
                    <!-- Already Voted Message -->
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle mr-2"></i>You have already voted. Thank you for participating!
                        <br><small class="mt-2 d-block">
                            <i class="fas fa-link mr-1"></i>Your votes are securely recorded on the blockchain.
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function selectCandidate(card, position, candidateId) {
            // Remove selection from other candidates in the same position
            const positionSection = card.closest('.position-section');
            positionSection.querySelectorAll('.candidate-card').forEach(c => {
                c.classList.remove('selected');
                c.querySelector('input[type="radio"]').checked = false;
            });
            
            // Select this candidate
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
        }
    </script>

        

<footer class="bg-dark text-white pt-4 pb-3">
    <div class="container">
        <div class="row text-center text-md-start">

            <!-- Quick Links -->
            <div class="col-md-4 mb-3">
                <h6 class="text-uppercase fw-bold">Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="index.html" class="text-white-50">Home</a></li>
                    <li><a href="register.php" class="text-white-50">Register</a></li>
                    <li><a href="login.php" class="text-white-50">Login</a></li>
                    <li><a href="#features" class="text-white-50">Features</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-md-4 mb-3">
                <h6 class="text-uppercase fw-bold">Support</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-white-50">Help Center</a></li>
                    <li><a href="#" class="text-white-50">Contact Us</a></li>
                    <li><a href="user-guide.pdf" class="text-white-50">User Guide</a></li>
                    <li><a href="#" class="text-white-50">FAQ</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-md-4 mb-3">
                <h6 class="text-uppercase fw-bold">Contact</h6>
                <p class="text-white-50 mb-1">
                    <i class="fas fa-phone me-2"></i> +254 759 075 816
                </p>
                <p class="text-white-50 mb-1">
                    <i class="fas fa-envelope me-2"></i> daviesqunyu@gmail.com
                </p>
                <p class="text-white-50">
                    <i class="fas fa-map-marker-alt me-2"></i> Nairobi, Kenya
                </p>
            </div>

        </div>

        <hr class="border-secondary">

        <!-- Footer Bottom -->
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <small class="text-white-50">
                    &copy; <?php echo date('Y'); ?> E-Voting System. All rights reserved.
                </small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small class="text-white-50">
                    Created by <strong>Davis Kunyu</strong> | Final Year Project
                </small>
            </div>
        </div>
    </div>
</footer>

</body>
</html>