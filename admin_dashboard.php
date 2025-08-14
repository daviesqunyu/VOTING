<?php 
include('functions.php');

// Only admins
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['msg'] = "You must be an admin";
    header('location: login.php');
    exit();
}

// Get quick statistics for dashboard
function getDashboardStats() {
    global $db;
    
    $stats = [
        'total_voters' => 0,
        'total_candidates' => 0,
        'total_votes' => 0,
        'voted_users' => 0,
        'turnout_percentage' => 0,
        'positions_count' => 0
    ];
    
    try {
        // Get registered voters
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
        
        // Calculate turnout percentage
        if ($stats['total_voters'] > 0) {
            $stats['turnout_percentage'] = round(($stats['voted_users'] / $stats['total_voters']) * 100, 2);
        }
        
        // Get number of positions
        $positions_result = mysqli_query($db, "SELECT COUNT(DISTINCT position_type) as total FROM candidates WHERE status = 1");
        if ($positions_result) {
            $stats['positions_count'] = mysqli_fetch_assoc($positions_result)['total'];
        }
        
    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
    }
    
    return $stats;
}

$dashboard_stats = getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | E-Voting System</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- External CSS -->
  <link rel="stylesheet" href="css/style.css">
  
  <style>
    .dashboard {
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .dash-header {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .dash-header h1 {
      color: white;
      margin: 0;
      font-size: 2rem;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 20px;
      color: white;
    }
    
    .btn-logout {
      background: rgba(220, 53, 69, 0.8);
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .btn-logout:hover {
      background: rgba(220, 53, 69, 1);
      transform: translateY(-2px);
    }
    
    .dash-main {
      padding: 30px;
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .stats-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .stat-card {
      background: rgba(255, 255, 255, 0.95);
      padding: 25px;
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-card i {
      font-size: 2.5rem;
      margin-bottom: 15px;
    }
    
    .stat-card h3 {
      font-size: 2rem;
      margin: 10px 0;
      color: #333;
    }
    
    .stat-card p {
      color: #666;
      margin: 0;
      font-size: 1.1rem;
    }
    
    .card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      margin-bottom: 25px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: transform 0.3s ease;
    }
    
    .card:hover {
      transform: translateY(-3px);
    }
    
    .card h2 {
      background: linear-gradient(135deg, #007bff, #0056b3);
      color: white;
      margin: 0;
      padding: 20px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 1.3rem;
    }
    
    .panel {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      background: #f8f9fa;
    }
    
    .panel.open {
      max-height: 300px;
    }
    
    .action-btn {
      display: block;
      padding: 15px 20px;
      text-decoration: none;
      color: #333;
      border-bottom: 1px solid #e9ecef;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .action-btn:hover {
      background: #007bff;
      color: white;
      transform: translateX(10px);
    }
    
    .action-btn:last-child {
      border-bottom: none;
    }
    
    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .quick-action-card {
      background: rgba(255, 255, 255, 0.95);
      padding: 25px;
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    
    .quick-action-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 35px rgba(0,0,0,0.15);
    }
    
    .quick-action-card i {
      font-size: 3rem;
      margin-bottom: 15px;
    }
    
    .quick-action-card h3 {
      margin: 15px 0;
      color: #333;
    }
    
    .quick-action-card p {
      color: #666;
      margin-bottom: 20px;
    }
    
    .quick-action-btn {
      display: inline-block;
      padding: 12px 24px;
      background: linear-gradient(135deg, #007bff, #0056b3);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    
    .quick-action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .results-preview {
      background: rgba(255, 255, 255, 0.95);
      padding: 25px;
      border-radius: 15px;
      margin-bottom: 30px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .results-preview h3 {
      color: #333;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .results-controls {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }
    
    .control-btn {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }
    
    .control-btn.view {
      background: #28a745;
      color: white;
    }
    
    .control-btn.toggle {
      background: #ffc107;
      color: #333;
    }
    
    .control-btn.export {
      background: #17a2b8;
      color: white;
    }
    
    .control-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    @media (max-width: 768px) {
      .dash-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .stats-overview {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .quick-actions {
        grid-template-columns: 1fr;
      }
      
      .results-controls {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <header class="dash-header">
      <h1><i class="fas fa-crown" style="color: gold;"></i> Admin Control Panel</h1>
      <div class="user-info">
        <span>Welcome, <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></span>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </header>

    <main class="dash-main">
      <!-- Statistics Overview -->
      <div class="stats-overview">
        <div class="stat-card">
          <i class="fas fa-users" style="color: #007bff;"></i>
          <h3><?php echo number_format($dashboard_stats['total_voters']); ?></h3>
          <p>Registered Voters</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-vote-yea" style="color: #28a745;"></i>
          <h3><?php echo number_format($dashboard_stats['total_votes']); ?></h3>
          <p>Total Votes Cast</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-percentage" style="color: #ffc107;"></i>
          <h3><?php echo $dashboard_stats['turnout_percentage']; ?>%</h3>
          <p>Voter Turnout</p>
        </div>
        <div class="stat-card">
          <i class="fas fa-user-tie" style="color: #6f42c1;"></i>
          <h3><?php echo number_format($dashboard_stats['total_candidates']); ?></h3>
          <p>Active Candidates</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <div class="quick-action-card">
          <i class="fas fa-chart-bar" style="color: #007bff;"></i>
          <h3>View Live Results</h3>
          <p>Monitor real-time election results and statistics</p>
          <a href="results.php" class="quick-action-btn">View Results</a>
        </div>
        <div class="quick-action-card">
          <i class="fas fa-user-plus" style="color: #28a745;"></i>
          <h3>Add Candidate</h3>
          <p>Register new candidates for the election</p>
          <a href="add_candidates.php" class="quick-action-btn">Add Candidate</a>
        </div>
        <div class="quick-action-card">
          <i class="fas fa-users" style="color: #ffc107;"></i>
          <h3>Manage Voters</h3>
          <p>Add or remove registered voters</p>
          <a href="add_voter.php" class="quick-action-btn">Manage Voters</a>
        </div>
      </div>

      <!-- Results Management -->
      <div class="results-preview">
        <h3><i class="fas fa-chart-line" style="color: #007bff;"></i> Election Results Management</h3>
        <div class="results-controls">
          <a href="results.php" class="control-btn view">
            <i class="fas fa-eye"></i> View Results
          </a>
          <a href="results.php?toggle_visibility=1" class="control-btn toggle">
            <i class="fas fa-eye-slash"></i> Toggle Visibility
          </a>
          <a href="results.php?export=1" class="control-btn export">
            <i class="fas fa-download"></i> Export Results
          </a>
        </div>
      </div>

      <!-- Candidate Management -->
      <section class="card" onclick="toggle('cand-panel')">
        <h2><i class="fas fa-user-tie" style="color: #007bff;"></i> Candidate Management</h2>
        <div id="cand-panel" class="panel">
          <a href="add_candidates.php" class="action-btn">
            <i class="fas fa-user-plus" style="color: #28a745;"></i> Add Candidate
          </a>
          <a href="remove_candidates.php" class="action-btn">
            <i class="fas fa-user-minus" style="color: #dc3545;"></i> Remove Candidate
          </a>
          <a href="view_candidates.php" class="action-btn">
            <i class="fas fa-eye" style="color: #17a2b8;"></i> View Candidates
          </a>
        </div>
      </section>

      <!-- Voter Management -->
      <section class="card" onclick="toggle('voter-panel')">
        <h2><i class="fas fa-users" style="color: #28a745;"></i> Voter Management</h2>
        <div id="voter-panel" class="panel">
          <a href="add_voter.php" class="action-btn">
            <i class="fas fa-user-plus" style="color: #28a745;"></i> Add Voter
          </a>
          <a href="remove_voter.php" class="action-btn">
            <i class="fas fa-user-minus" style="color: #dc3545;"></i> Remove Voter
          </a>
          <a href="view_voter.php" class="action-btn">
            <i class="fas fa-eye" style="color: #17a2b8;"></i> View Voters
          </a>
        </div>
      </section>

      <!-- System Management -->
      <section class="card" onclick="toggle('system-panel')">
        <h2><i class="fas fa-cogs" style="color: #6f42c1;"></i> System Management</h2>
        <div id="system-panel" class="panel">
          <a href="index.php" class="action-btn">
            <i class="fas fa-home" style="color: #007bff;"></i> Go to Homepage
          </a>
          <a href="vote.php" class="action-btn">
            <i class="fas fa-vote-yea" style="color: #28a745;"></i> View Voting Page
          </a>
          <a href="profile.php" class="action-btn">
            <i class="fas fa-user-cog" style="color: #ffc107;"></i> Profile Settings
          </a>
        </div>
      </section>
    </main>
  </div>

  <script>
    function toggle(id) {
      document.getElementById(id).classList.toggle('open');
    }
    
    // Auto-refresh dashboard stats every 30 seconds
    setTimeout(function() {
      location.reload();
    }, 30000);
  </script>
</body>
</html>
