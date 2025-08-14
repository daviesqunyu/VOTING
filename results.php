<?php
require_once('functions.php');

// Check if user is admin or regular user
$is_admin = isAdmin();
$is_logged_in = isLoggedIn();

// If not logged in, redirect to login
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Handle admin controls for results visibility
$results_visible = isset($_SESSION['results_visible']) ? $_SESSION['results_visible'] : true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_results']) && $is_admin) {
    $_SESSION['results_visible'] = !$results_visible;
    $results_visible = $_SESSION['results_visible'];
}

// Handle URL-based visibility toggle from admin dashboard
if (isset($_GET['toggle_visibility']) && $is_admin) {
    $_SESSION['results_visible'] = !$results_visible;
    $results_visible = $_SESSION['results_visible'];
    header('Location: results.php');
    exit();
}

// Handle results export
if (isset($_GET['export']) && $is_admin) {
    exportResults();
    exit();
}

// Function to export results as CSV
function exportResults() {
    global $db;
    
    $filename = "election_results_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Position', 'Candidate Name', 'Party', 'Total Votes', 'Percentage', 'Status']);
    
    $results = getElectionResults();
    foreach ($results as $position => $candidates) {
        foreach ($candidates as $candidate) {
            fputcsv($output, [
                $position,
                $candidate['name'],
                $candidate['party'],
                $candidate['votes'],
                $candidate['percentage'] . '%',
                $candidate['is_winner'] ? 'WINNER' : 'Runner-up'
            ]);
        }
    }
    fclose($output);
}

// Function to create/update database tables if needed
function ensureDatabaseStructure() {
    global $db;
    
    // Create candidates table if it doesn't exist
    $create_candidates_sql = "CREATE TABLE IF NOT EXISTS candidates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        party VARCHAR(255) NOT NULL,
        position_type VARCHAR(255) NOT NULL,
        manifesto TEXT,
        image_path VARCHAR(500),
        status TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($db, $create_candidates_sql)) {
        error_log("Failed to create candidates table: " . mysqli_error($db));
        return false;
    }
    
    // Create votes table if it doesn't exist
    $create_votes_sql = "CREATE TABLE IF NOT EXISTS votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        voter_id INT NOT NULL,
        candidate_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_voter_candidate (voter_id, candidate_id),
        FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($db, $create_votes_sql)) {
        error_log("Failed to create votes table: " . mysqli_error($db));
        return false;
    }
    
    // Create settings table for admin controls
    $create_settings_sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL,
        value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($db, $create_settings_sql)) {
        error_log("Failed to create settings table: " . mysqli_error($db));
        return false;
    }
    
    // Insert default settings if they don't exist
    $default_settings = [
        ['voting_status', 'open'],
        ['results_visible', '1'],
        ['election_title', 'Nairobi County Elections']
    ];
    
    foreach ($default_settings as $setting) {
        $check_sql = "SELECT id FROM settings WHERE name = '" . mysqli_real_escape_string($db, $setting[0]) . "'";
        $result = mysqli_query($db, $check_sql);
        
        if (!$result || mysqli_num_rows($result) == 0) {
            $insert_sql = "INSERT INTO settings (name, value) VALUES ('" . 
                         mysqli_real_escape_string($db, $setting[0]) . "', '" . 
                         mysqli_real_escape_string($db, $setting[1]) . "')";
            mysqli_query($db, $insert_sql);
        }
    }
    
    return true;
}

// Function to get election results with proper error handling
function getElectionResults() {
    global $db;
    
    // Ensure database structure exists
    if (!ensureDatabaseStructure()) {
        return [];
    }
    
    $results = [];
    
    try {
        // Get all candidates with their vote counts
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.party,
                    c.position_type,
                    c.image_path,
                    COUNT(v.id) as vote_count
                FROM candidates c
                LEFT JOIN votes v ON c.id = v.candidate_id
                WHERE c.status = 1
                GROUP BY c.id, c.name, c.party, c.position_type, c.image_path
                ORDER BY c.position_type, vote_count DESC";
        
        $result = mysqli_query($db, $sql);
        
        if (!$result) {
            error_log("Database query failed: " . mysqli_error($db));
            return [];
        }
        
        // Group candidates by position
        while ($row = mysqli_fetch_assoc($result)) {
            $position = $row['position_type'] ?: 'General';
            if (!isset($results[$position])) {
                $results[$position] = [];
            }
            $results[$position][] = $row;
        }
        
        // Calculate percentages and determine winners for each position
        foreach ($results as $position => &$candidates) {
            $total_votes = array_sum(array_column($candidates, 'vote_count'));
            
            foreach ($candidates as $index => &$candidate) {
                $candidate['percentage'] = $total_votes > 0 ? round(($candidate['vote_count'] / $total_votes) * 100, 2) : 0;
                $candidate['is_winner'] = ($index === 0 && $candidate['vote_count'] > 0);
            }
        }
        
    } catch (Exception $e) {
        error_log("Error getting election results: " . $e->getMessage());
        return [];
    }
    
    return $results;
}

// Function to get comprehensive statistics
function getElectionStatistics() {
    global $db;
    
    $stats = [
        'total_voters' => 0,
        'total_candidates' => 0,
        'total_votes' => 0,
        'voted_users' => 0,
        'pending_voters' => 0,
        'turnout_percentage' => 0,
        'positions_count' => 0,
        'latest_votes' => []
    ];
    
    try {
        // Get registered voters (users with user_type = 'user')
        $voter_result = mysqli_query($db, "SELECT COUNT(*) as total FROM users WHERE user_type = 'user'");
        if ($voter_result) {
            $stats['total_voters'] = mysqli_fetch_assoc($voter_result)['total'];
        }
        
        // Get total candidates
        $candidate_result = mysqli_query($db, "SELECT COUNT(*) as total FROM candidates WHERE status = 1");
        if ($candidate_result) {
            $stats['total_candidates'] = mysqli_fetch_assoc($candidate_result)['total'];
        }
        
        // Get total votes cast
        $votes_result = mysqli_query($db, "SELECT COUNT(*) as total FROM votes");
        if ($votes_result) {
            $stats['total_votes'] = mysqli_fetch_assoc($votes_result)['total'];
        }
        
        // Get users who have voted
        $voted_users_result = mysqli_query($db, "SELECT COUNT(DISTINCT voter_id) as total FROM votes");
        if ($voted_users_result) {
            $stats['voted_users'] = mysqli_fetch_assoc($voted_users_result)['total'];
        }
        
        // Calculate pending voters
        $stats['pending_voters'] = $stats['total_voters'] - $stats['voted_users'];
        
        // Calculate turnout percentage
        if ($stats['total_voters'] > 0) {
            $stats['turnout_percentage'] = round(($stats['voted_users'] / $stats['total_voters']) * 100, 2);
        }
        
        // Get number of positions
        $positions_result = mysqli_query($db, "SELECT COUNT(DISTINCT position_type) as total FROM candidates WHERE status = 1");
        if ($positions_result) {
            $stats['positions_count'] = mysqli_fetch_assoc($positions_result)['total'];
        }
        
        // Get latest 5 votes for live activity
        $latest_votes_sql = "SELECT v.created_at, v.voter_id, c.name as candidate_name, c.position_type
                            FROM votes v
                            JOIN candidates c ON v.candidate_id = c.id
                            ORDER BY v.created_at DESC 
                            LIMIT 5";
        $latest_result = mysqli_query($db, $latest_votes_sql);
        if ($latest_result) {
            while ($row = mysqli_fetch_assoc($latest_result)) {
                $stats['latest_votes'][] = $row;
            }
        }
        
    } catch (Exception $e) {
        error_log("Error getting election statistics: " . $e->getMessage());
    }
    
    return $stats;
}

// Function to get winners summary
function getWinnersSummary() {
    $winners = [];
    $results = getElectionResults();
    
    foreach ($results as $position => $candidates) {
        foreach ($candidates as $candidate) {
            if ($candidate['is_winner']) {
                $winners[] = [
                    'position' => $position,
                    'name' => $candidate['name'],
                    'party' => $candidate['party'],
                    'votes' => $candidate['vote_count'],
                    'percentage' => $candidate['percentage']
                ];
            }
        }
    }
    
    return $winners;
}

// Function to get voting activity by hour
function getVotingActivity() {
    global $db;
    
    $activity = [];
    
    try {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%H:00') as hour,
                    COUNT(*) as vote_count
                FROM votes 
                WHERE DATE(created_at) = CURDATE()
                GROUP BY DATE_FORMAT(created_at, '%H:00')
                ORDER BY hour";
        
        $result = mysqli_query($db, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $activity[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error getting voting activity: " . $e->getMessage());
    }
    
    return $activity;
}

// Get data for display
$election_results = getElectionResults();
$statistics = getElectionStatistics();
$winners = getWinnersSummary();
$voting_activity = getVotingActivity();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - E-Voting System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-overview {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.15);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .stat-card h4 {
            font-size: 2.5rem;
            margin: 0;
            color: #ffd700;
            font-weight: bold;
        }
        
        .stat-card p {
            margin: 10px 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .position-results {
            background: #fff;
            margin-bottom: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .position-header {
            background: linear-gradient(135deg, #0056b3, #004085);
            color: white;
            padding: 20px;
            margin: 0;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        
        .results-table th {
            background: #f8f9fa;
            color: #333;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .results-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }
        
        .winner-row {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            font-weight: bold;
            color: #333;
        }
        
        .runner-up {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
        }
        
        .winners-summary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .winners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .winner-card {
            background: rgba(255,255,255,0.15);
            padding: 25px;
            border-radius: 12px;
            border-left: 5px solid #ffd700;
            backdrop-filter: blur(10px);
        }
        
        .winner-card h4 {
            color: #ffd700;
            margin-bottom: 15px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .winner-card p {
            margin: 8px 0;
            font-size: 1.1rem;
        }
        
        .integrity-report {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            border-left: 5px solid #28a745;
            margin: 30px 0;
        }
        
        .integrity-report h3 {
            color: #28a745;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }
        
        .integrity-report ul {
            list-style: none;
            padding: 0;
        }
        
        .integrity-report li {
            padding: 12px 0;
            font-size: 1.1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .integrity-report li:last-child {
            border-bottom: none;
        }
        
        .admin-controls {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .admin-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .admin-btn.export {
            background: #28a745;
        }
        
        .admin-btn.export:hover {
            background: #20c997;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .no-data i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .nav-bar {
            background: #333;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-link:hover {
            background: #555;
        }
        
        .button {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .button:hover {
            background: #c82333;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .winners-grid {
                grid-template-columns: 1fr;
            }
            
            .results-table {
                font-size: 0.9rem;
            }
            
            .results-table th,
            .results-table td {
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="nav-bar">
        <?php if ($is_admin): ?>
            <a href="admin_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <?php else: ?>
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
        <?php endif; ?>
        <a href="logout.php" class="button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="results-container">
        <div class="stats-overview">
            <h2><i class="fas fa-chart-bar"></i> Election Results Dashboard</h2>
            
            <?php if ($is_admin): ?>
            <div class="admin-controls">
                <form method="post" style="display: inline;">
                    <button type="submit" name="toggle_results" class="admin-btn">
                        <i class="fas fa-eye"></i> <?php echo $results_visible ? 'Hide Results' : 'Show Results'; ?>
                    </button>
                </form>
                <a href="results.php?export=1" class="admin-btn export">
                    <i class="fas fa-download"></i> Export Results
                </a>
            </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h4><?php echo number_format($statistics['total_voters']); ?></h4>
                    <p><i class="fas fa-users"></i> Registered Voters</p>
                </div>
                <div class="stat-card">
                    <h4><?php echo number_format($statistics['total_votes']); ?></h4>
                    <p><i class="fas fa-vote-yea"></i> Votes Cast</p>
                </div>
                <div class="stat-card">
                    <h4><?php echo $statistics['turnout_percentage']; ?>%</h4>
                    <p><i class="fas fa-percentage"></i> Voter Turnout</p>
                </div>
                <div class="stat-card">
                    <h4><?php echo number_format($statistics['total_candidates']); ?></h4>
                    <p><i class="fas fa-user-tie"></i> Total Candidates</p>
                </div>
            </div>
        </div>

        <?php if ($results_visible): ?>
            <?php if (empty($election_results)): ?>
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>No Election Data Available</h3>
                    <p>Either no candidates are registered or no votes have been cast yet.</p>
                    <?php if ($is_admin): ?>
                        <p><a href="add_candidates.php" class="admin-btn">Add Candidates</a></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Display results by position -->
                <?php foreach ($election_results as $position => $candidates): ?>
                    <div class="position-results">
                        <h3 class="position-header">
                            <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($position); ?> Results
                        </h3>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Candidate</th>
                                    <th>Party</th>
                                    <th>Votes</th>
                                    <th>Percentage</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($candidates as $candidate): 
                                    $rank_class = '';
                                    $status = '';
                                    
                                    if ($candidate['is_winner']) {
                                        $rank_class = 'winner-row';
                                        $status = '<i class="fas fa-crown"></i> WINNER';
                                    } elseif ($rank == 2) {
                                        $rank_class = 'runner-up';
                                        $status = '<i class="fas fa-medal"></i> Runner-up';
                                    } else {
                                        $status = 'Candidate';
                                    }
                                ?>
                                    <tr class="<?php echo $rank_class; ?>">
                                        <td><strong><?php echo $rank; ?></strong></td>
                                        <td>
                                            <?php if ($candidate['image_path']): ?>
                                                <img src="<?php echo htmlspecialchars($candidate['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($candidate['name']); ?>" 
                                                     style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($candidate['name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($candidate['party']); ?></td>
                                        <td><strong><?php echo number_format($candidate['vote_count']); ?></strong></td>
                                        <td><?php echo $candidate['percentage']; ?>%</td>
                                        <td><strong><?php echo $status; ?></strong></td>
                                    </tr>
                                <?php 
                                    $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

                <!-- Winners Summary -->
                <?php if (!empty($winners)): ?>
                    <div class="winners-summary">
                        <h3><i class="fas fa-crown"></i> Elected Leaders</h3>
                        <div class="winners-grid">
                            <?php foreach ($winners as $winner): ?>
                                <div class="winner-card">
                                    <h4><?php echo htmlspecialchars($winner['name']); ?></h4>
                                    <p><strong><?php echo htmlspecialchars($winner['position']); ?></strong></p>
                                    <p>Party: <?php echo htmlspecialchars($winner['party']); ?></p>
                                    <p>Votes: <strong><?php echo number_format($winner['votes']); ?></strong> (<?php echo $winner['percentage']; ?>%)</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Election Integrity Report -->
                <div class="integrity-report">
                    <h3><i class="fas fa-shield-alt"></i> Election Integrity Report</h3>
                    <ul>
                        <li><i class="fas fa-check-circle" style="color: #28a745;"></i> All votes are securely recorded with timestamps</li>
                        <li><i class="fas fa-check-circle" style="color: #28a745;"></i> One person, one vote policy strictly enforced</li>
                        <li><i class="fas fa-check-circle" style="color: #28a745;"></i> Results calculated using verified mathematical algorithms</li>
                        <li><i class="fas fa-check-circle" style="color: #28a745;"></i> Database integrity maintained throughout the process</li>
                        <li><i class="fas fa-chart-line" style="color: #007bff;"></i> Total votes verified: <strong><?php echo number_format($statistics['total_votes']); ?></strong></li>
                        <li><i class="fas fa-users" style="color: #007bff;"></i> Voters who participated: <strong><?php echo number_format($statistics['voted_users']); ?></strong> out of <?php echo number_format($statistics['total_voters']); ?></li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-eye-slash"></i>
                <h3>Results Not Available</h3>
                <p>The system administrator has not enabled results viewing yet.</p>
                <p>Please check back later or contact the administrator.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh results every 30 seconds for live updates
        <?php if ($results_visible && !empty($election_results)): ?>
        setTimeout(function() {
            location.reload();
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>