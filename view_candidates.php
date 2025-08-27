<?php
include('functions.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enhanced authentication check
if (!isLoggedIn()) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header('Location: login.php');
    exit;
}

if (!isAdmin()) {
    $_SESSION['error'] = "Unauthorized access. Admin privileges required.";
    header('Location: index.php');
    exit;
}

// Handle delete with confirmation and error handling
if (isset($_GET['delete_id'])) {
    if (deleteCandidate((int)$_GET['delete_id'])) {
        $_SESSION['success'] = "Candidate removed successfully.";
    } else {
        $_SESSION['error'] = "Failed to remove candidate.";
    }
    header('Location: view_candidates.php');
    exit;
}

// Get all candidates with error handling
$candidates = getAllCandidates();
if ($candidates === false) {
    $_SESSION['error'] = "Error loading candidates. Please try again.";
    $candidates = []; // Set empty array to prevent errors
}

// Filter candidates by position if filter is set
if (isset($_GET['position_filter']) && $_GET['position_filter'] !== '') {
    $position_filter = $_GET['position_filter'];
    $candidates = array_filter($candidates, function($candidate) use ($position_filter) {
        // Use 'position_type' as per add_candidates.php
        return isset($candidate['position_type']) && $candidate['position_type'] === $position_filter;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Candidates | E-Voting Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .candidate-card {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            background: #fff;
        }
        .candidate-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        .action-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .manifesto-preview {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }
    </style>
</head>

<body>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-vote-yea me-2"></i>E-Voting System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_candidates.php"><i class="fas fa-user-plus me-1"></i> Add Candidate</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_candidates.php"><i class="fas fa-users me-1"></i> View Candidates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=1"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="candidate-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-users me-2"></i>All Candidates</h2>
                <a href="add_candidates.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New
                </a>
            </div>
            <div class="mb-3">
    <form method="get" class="row g-3">
        <div class="col-md-4">
            <select name="position_filter" class="form-select" onchange="this.form.submit()">
                <option value="">All Positions</option>
                <option value="President" <?= ($_GET['position_filter'] ?? '') == 'President' ? 'selected' : '' ?>>President</option>
                <option value="Governor" <?= ($_GET['position_filter'] ?? '') == 'Governor' ? 'selected' : '' ?>>Governor</option>
                <option value="Senator" <?= ($_GET['position_filter'] ?? '') == 'Senator' ? 'selected' : '' ?>>Senator</option>
                <option value="MP" <?= ($_GET['position_filter'] ?? '') == 'MP' ? 'selected' : '' ?>>MP</option>
                <option value="MCA" <?= ($_GET['position_filter'] ?? '') == 'MCA' ? 'selected' : '' ?>>MCA</option>
                <option value="Women Rep" <?= ($_GET['position_filter'] ?? '') == 'Women Rep' ? 'selected' : '' ?>>Women representative</option>

            </select>
        </div>
    </form>
</div>



            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (empty($candidates)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No candidates found. Add your first candidate.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Party</th>
                                <th>Status</th>
                                <th>Manifesto</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidates as $candidate): ?>
                                <tr>
                                    <td><?= $candidate['id'] ?></td>
                                    <td>
                                        <?php if (!empty($candidate['image_path'])): ?>
                                            <img src="<?= htmlspecialchars($candidate['image_path']) ?>" 
                                                 alt="<?= htmlspecialchars($candidate['name']) ?>" 
                                                 class="candidate-image">
                                        <?php else: ?>
                                            <div class="candidate-image bg-secondary text-white d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($candidate['name']) ?></td>
                                    <td><?= htmlspecialchars($candidate['party']) ?></td>
                                    <td>
                                        <span class="badge <?= $candidate['status'] ? 'bg-success' : 'bg-secondary' ?> status-badge">
                                            <?= $candidate['status'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td class="manifesto-preview" title="<?= htmlspecialchars($candidate['manifesto']) ?>">
                                        <?= htmlspecialchars($candidate['manifesto']) ?>
                                    </td>
                                    <td>
                                        <a href="edit_candidate.php?id=<?= $candidate['id'] ?>" 
                                           class="btn btn-sm btn-primary action-btn"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_candidates.php?delete_id=<?= $candidate['id'] ?>" 
                                           class="btn btn-sm btn-danger action-btn" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this candidate?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>


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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>