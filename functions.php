<?php
// Main functions file for E-Voting System
// Handles database connection, user authentication, and voting functions

session_start();

// Database connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = mysqli_connect('localhost', 'root', '', 'multi-login');
    if (!$db) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    mysqli_set_charset($db, 'utf8');
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Global variables for form data
$username = "";
$email = "";
$ID = "";
$errors = [];

// Helper function to escape user input
if (!function_exists('e')) {
    function e($val) {
        global $db;
        return mysqli_real_escape_string($db, trim($val));
    }
}

// Display error messages
if (!function_exists('display_error')) {
    function display_error() {
        global $errors;
        if ($errors) {
            echo '<div class="alert alert-danger">';
            foreach ($errors as $err) {
                echo htmlspecialchars($err) . "<br>";
            }
            echo '</div>';
        }
    }
}

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user']);
    }
}

// Check if user is admin
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isLoggedIn() && $_SESSION['user']['user_type'] === 'admin';
    }
}

// Get user by ID
if (!function_exists('getUserById')) {
    function getUserById($id) {
        global $db;
        $id = intval($id);
        $res = mysqli_query($db, "SELECT * FROM users WHERE ID=$id LIMIT 1");
        return mysqli_fetch_assoc($res);
    }
}

// User registration function
if (!function_exists('registerUser')) {
    function registerUser() {
        global $db, $errors, $username, $email, $ID;

        // Get form data
        $username = e($_POST['username'] ?? '');
        $ID = e($_POST['ID'] ?? '');
        $email = e($_POST['email'] ?? '');
        $p1 = e($_POST['password_1'] ?? '');
        $p2 = e($_POST['password_2'] ?? '');

        // Validate input
        if (!$username) $errors[] = "Username is required";
        if (!$ID) $errors[] = "National ID is required";
        if (!$email) $errors[] = "Email is required";
        if (!$p1) $errors[] = "Password is required";
        if ($p1 !== $p2) $errors[] = "Passwords do not match";

        // Check if user already exists
        if (empty($errors)) {
            $check_sql = "SELECT * FROM users WHERE username='$username' OR ID='$ID' OR email='$email' LIMIT 1";
            $check_result = mysqli_query($db, $check_sql);
            if ($check_result && mysqli_num_rows($check_result) > 0) {
                $existing = mysqli_fetch_assoc($check_result);
                if ($existing['username'] === $username) {
                    $errors[] = "Username already exists. Please choose a different username.";
                }
                if ($existing['ID'] === $ID) {
                    $errors[] = "National ID already registered. Please use a different ID or login if you have an account.";
                }
                if ($existing['email'] === $email) {
                    $errors[] = "Email already registered. Please use a different email or login if you have an account.";
                }
            }
        }

        // Register user if no errors
        if (empty($errors)) {
            $hash = password_hash($p1, PASSWORD_DEFAULT);
            $role = in_array($_POST['user_type'] ?? '', ['admin','user']) ? e($_POST['user_type']) : 'user';
            
            $sql = "INSERT INTO users (username, ID, email, user_type, password) VALUES ('$username','$ID','$email','$role','$hash')";
            
            if (mysqli_query($db, $sql)) {
                $_SESSION['success'] = "Registration successful! Please log in.";
                header('Location: login.php');
                exit();
            } else {
                $errors[] = "Registration failed: " . mysqli_error($db);
            }
        }
    }
}

// User login function
if (!function_exists('loginUser')) {
    function loginUser() {
        global $db, $errors, $username, $ID;
        
        $username = e($_POST['username'] ?? '');
        $ID = e($_POST['ID'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input
        if (!$username) $errors[] = "Username is required";
        if (!$ID) $errors[] = "National ID is required";
        if (!$password) $errors[] = "Password is required";

        if (empty($errors)) {
            $sql = "SELECT * FROM users WHERE username='$username' AND ID='$ID' LIMIT 1";
            $res = mysqli_query($db, $sql);
            if ($res && mysqli_num_rows($res) === 1) {
                $user = mysqli_fetch_assoc($res);
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $user;
                    $_SESSION['success'] = "Welcome, " . htmlspecialchars($user['username']) . "!";

                    // Redirect based on user type
                    if ($user['user_type'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit();
                }
            }
            $errors[] = "Wrong username/ID/password combination";
        }
    }
}

// User logout function logs out users and takes them to login page
if (!function_exists('logoutUser')) {
    function logoutUser() {
        session_destroy();
        header('Location: login.php');
        exit();
    }
}


// Get voting status
if (!function_exists('getVotingStatus')) {
    function getVotingStatus() {
        return [
     //I Have set default to active status which is (true)
            'is_active' => true,
            'message' => 'Voting is currently active'
        ];
    }
}

// Get voter information
if (!function_exists('getVoterInfo')) {
    function getVoterInfo($voter_id) {
        global $db;
        $voter_id = intval($voter_id);
        $sql = "SELECT * FROM users WHERE ID = $voter_id AND user_type = 'user' LIMIT 1";
        $result = mysqli_query($db, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            return [
                'voter_id' => $user['ID'],
                'full_name' => $user['username'],
                'email' => $user['email']
            ];
        }
        return null;
    }
}

// Check if voter has already voted
if (!function_exists('hasVoterVoted')) {
    function hasVoterVoted($voter_id) {
        global $db;
        $voter_id = intval($voter_id);
        
        // Check if votes table exists
        $table_check = mysqli_query($db, "SHOW TABLES LIKE 'votes'");
        if (!$table_check || mysqli_num_rows($table_check) == 0) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as vote_count FROM votes WHERE voter_id = $voter_id";
        $result = mysqli_query($db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return intval($row['vote_count']) > 0;
        }
        return false;
    }
}

// Get voter's previous votes and 
//records them with an output form in my_vote.php page
if (!function_exists('getVoterVotes')) {
    function getVoterVotes($voter_id) {
        global $db;
        $voter_id = intval($voter_id);
        
        $sql = "SELECT v.*, c.name as candidate_name, c.position_type, c.image_path, c.party 
                FROM votes v 
                JOIN candidates c ON v.candidate_id = c.id 
                WHERE v.voter_id = $voter_id
                ORDER BY c.position_type, c.name";
        $result = mysqli_query($db, $sql);
        $votes = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $votes[] = $row;
            }
        }
        return $votes;
    }
}

// Get candidates grouped by position
if (!function_exists('getCandidatesByPosition')) {
    function getCandidatesByPosition() {
        global $db;
        
        // Check if candidates table exists
        $table_check = mysqli_query($db, "SHOW TABLES LIKE 'candidates'");
        if (!$table_check || mysqli_num_rows($table_check) == 0) {
            return [];
        }
        
        $sql = "SELECT * FROM candidates WHERE status = 1 ORDER BY position_type, name";
        $result = mysqli_query($db, $sql);
        $candidates_by_position = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $position = $row['position_type'] ?? 'General';
                if (!isset($candidates_by_position[$position])) {
                    $candidates_by_position[$position] = [];
                }
                $candidates_by_position[$position][] = $row;
            }
        }
        return $candidates_by_position;
    }
}

// Get candidate by ID
if (!function_exists('getCandidateById')) {
    function getCandidateById($candidate_id) {
        global $db;
        $candidate_id = intval($candidate_id);
        
        $sql = "SELECT * FROM candidates WHERE id = $candidate_id AND status = 1 LIMIT 1";
        $result = mysqli_query($db, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return false;
    }
}

// Submit votes
if (!function_exists('submitVotes')) {
    function submitVotes($voter_id, $votes) {
        global $db;
        
        // Validate inputs
        if (!$db) {
            error_log("submitVotes: Database connection is null");
            return false;
        }
        
        $voter_id = intval($voter_id);
        if ($voter_id <= 0) {
            error_log("submitVotes: Invalid voter_id: $voter_id");
            return false;
        }
        
        if (empty($votes) || !is_array($votes)) {
            error_log("submitVotes: No votes provided or invalid format");
            return false;
        }
        
        // Check if user already voted
        if (hasVoterVoted($voter_id)) {
            error_log("submitVotes: User $voter_id has already voted");
            return false;
        }

        // Ensure votes table exists with blockchain transaction_id column
        $create_votes_sql = "CREATE TABLE IF NOT EXISTS votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            voter_id INT NOT NULL,
            candidate_id INT NOT NULL,
            transaction_id VARCHAR(64) NULL,
            block_hash VARCHAR(64) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_voter_candidate (voter_id, candidate_id),
            INDEX idx_transaction_id (transaction_id)
        )";
        
        if (!mysqli_query($db, $create_votes_sql)) {
            error_log("Failed to create votes table: " . mysqli_error($db));
            return false;
        }
        
        // Add blockchain columns if they don't exist (for existing tables)
        $check_cols = mysqli_query($db, "SHOW COLUMNS FROM votes LIKE 'transaction_id'");
        if (!$check_cols || mysqli_num_rows($check_cols) == 0) {
            mysqli_query($db, "ALTER TABLE votes ADD COLUMN transaction_id VARCHAR(64) NULL");
        }
        $check_cols2 = mysqli_query($db, "SHOW COLUMNS FROM votes LIKE 'block_hash'");
        if (!$check_cols2 || mysqli_num_rows($check_cols2) == 0) {
            mysqli_query($db, "ALTER TABLE votes ADD COLUMN block_hash VARCHAR(64) NULL");
        }

        // Load blockchain module
        require_once 'blockchain.php';

        // Uses transaction for data integrity
        try {
            mysqli_autocommit($db, false);
            
            $stmt = $db->prepare("INSERT INTO votes (voter_id, candidate_id, transaction_id, block_hash) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $db->error);
            }
            
            $transaction_ids = [];
            
            foreach ($votes as $candidate_id) {
                $candidate_id = intval($candidate_id);
                
                // Validate candidate exists and is active
                if (!getCandidateById($candidate_id)) {
                    throw new Exception("Invalid candidate ID: $candidate_id");
                }
                
                // Record vote on blockchain
                $blockchain_result = recordVoteOnBlockchain($voter_id, $candidate_id);
                
                if (!$blockchain_result) {
                    error_log("Failed to record vote on blockchain for voter $voter_id, candidate $candidate_id");
                    // Continue with database insertion even if blockchain fails
                    $transaction_id = null;
                    $block_hash = null;
                } else {
                    $transaction_id = $blockchain_result['transaction_id'];
                    $block_hash = $blockchain_result['hash'];
                    $transaction_ids[] = $transaction_id;
                }
                
                $stmt->bind_param("iiss", $voter_id, $candidate_id, $transaction_id, $block_hash);
                if (!$stmt->execute()) {
                    throw new Exception("Insert failed for candidate $candidate_id: " . $stmt->error);
                }
            }
            
            $stmt->close();
            mysqli_commit($db);
            mysqli_autocommit($db, true);
            
            // Store transaction IDs in session for display
            $_SESSION['last_vote_transactions'] = $transaction_ids;
            
            return true;
            
        } catch (Exception $e) {
            if (isset($stmt)) {
                $stmt->close();
            }
            mysqli_rollback($db);
            mysqli_autocommit($db, true);
            error_log("submitVotes error: " . $e->getMessage());
            return false;
        }
    }
}

// Check if results can be shown or hidden
// CHANGING TRUE TO FALSE WILL TAGGLE RESULTS VIEW FOR VOTERS 
//FOR ADMIN I USE THE HIDE RESUSLTS BUTTON IN ADMIN DASHBOURD
if (!function_exists('canShowResults')) {
            function canShowResults() {
                //(I HAVE SET VIEW RESULTS VIEW  DEFAULT TO TRUE)
                return isset($_SESSION['results_visible']) ? $_SESSION['results_visible'] : true;
            }
        }

// Get voting results
if (!function_exists('getVotingResults')) {
    function getVotingResults() {
        global $db;
        
        // Ensure database structure exists
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
        mysqli_query($db, $create_candidates_sql);
        
        $create_votes_sql = "CREATE TABLE IF NOT EXISTS votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            voter_id INT NOT NULL,
            candidate_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_voter_candidate (voter_id, candidate_id)
        )";
        mysqli_query($db, $create_votes_sql);
        
        $sql = "
            SELECT c.position_type, c.name, c.party, c.image_path, COUNT(v.id) as votes
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id
            WHERE c.status = 1
            GROUP BY c.id, c.position_type, c.name, c.party, c.image_path
            ORDER BY c.position_type, votes DESC";
        
        $res = mysqli_query($db, $sql);
        if (!$res) {
            error_log("Error getting voting results: " . mysqli_error($db));
            return [];
        }
        
        $results = [];
        $totals = [];
        
        while ($row = mysqli_fetch_assoc($res)) {
            $position = $row['position_type'] ?: 'General';
            $results[$position][] = $row;
            $totals[$position] = ($totals[$position] ?? 0) + $row['votes'];
        }
        
        // Calculate percentages and determine winners
        foreach ($results as $position => &$candidates) {
            $total = $totals[$position] ?: 1;
            foreach ($candidates as $index => &$candidate) {
                $candidate['percentage'] = round(($candidate['votes'] / $total) * 100, 1);
                $candidate['is_winner'] = ($index === 0 && $candidate['votes'] > 0);
            }
        }
        
        return $results;
    }
}

// Get existing parties for dropdown
function getExistingParties() {
    global $db;
    $sql = "SELECT DISTINCT party FROM candidates WHERE party IS NOT NULL AND party != ''";
    $result = mysqli_query($db, $sql);
    $parties = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $parties[] = $row['party'];
        }
    }
    return $parties;
}

// Add candidate
function addCandidate($name, $party, $position_type, $manifesto, $image_path = null, $status = 1) {
    global $db;
    if (!$db) {
        error_log("Database connection not available");
        return false;
    }
    $stmt = $db->prepare("INSERT INTO candidates (name, party, position_type, manifesto, image_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        error_log("Prepare failed: " . $db->error);
        return false;
    }
    $stmt->bind_param("sssssi", $name, $party, $position_type, $manifesto, $image_path, $status);
    $success = $stmt->execute();
    if (!$success) {
        error_log("Execute failed: " . $stmt->error);
    }
    $stmt->close();
    return $success;
}

// Get all candidates
function getAllCandidates() {
    global $db;
    $sql = "SELECT * FROM candidates ORDER BY created_at DESC";
    $res = mysqli_query($db, $sql);
    if (!$res) {
        error_log("Query failed: " . mysqli_error($db));
        return [];
    }
    $candidates = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $candidates[] = $row;
    }
    return $candidates;
}


// Delete candidate
function deleteCandidate($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM candidates WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $db->error);
        return false;
    }
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    if (!$success) {
        error_log("Execute failed: " . $stmt->error);
    }
    $stmt->close();
    return $success;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register_btn'])) registerUser();
    if (isset($_POST['login_btn'])) loginUser();
}

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}
?>