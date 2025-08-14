<?php
include('functions.php');

// login authentication check

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isAdmin()) {
    $_SESSION['error'] = "Unauthorized access. Admin privileges required.";
    header('Location: index.php');
    exit;
}

// Initialize variables
$error = $success = "";
$target_file = "";
$upload_success = false;
$form_data = [
    'name' => '',
    'party' => '',
    'position_type' => '',
    'manifesto' => '',
    'status' => 1
];

// Define these variables globally
$max_size = 2 * 1024 * 1024; // 2MB
$allowed_types = ['jpg', 'jpeg', 'png'];

// Handles the image upload process
 
function handleImageUpload()
{
    global $max_size, $allowed_types;

    // Checks if file was actually uploaded
    if (!isset($_FILES["candidate_image"]) || $_FILES["candidate_image"]["error"] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => "File upload error occurred."];
    }

    $target_dir = "uploads/candidates/";
    
    // Creates file / directory if it doesn't exist
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            return ['success' => false, 'error' => "Failed to create upload directory."];
        }
    }

    // Verifys that temporary file exists
    if (!file_exists($_FILES["candidate_image"]["tmp_name"])) {
        return ['success' => false, 'error' => "Temporary file not found."];
    }

    // Validates image
    $image_info = getimagesize($_FILES["candidate_image"]["tmp_name"]);
    if ($image_info === false) {
        return ['success' => false, 'error' => "File is not a valid image."];
    }

    // Generates unique filename
    $filename = uniqid() . '_' . basename($_FILES["candidate_image"]["name"]);
    $target_file = $target_dir . $filename;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Checks file size
    if ($_FILES["candidate_image"]["size"] > $max_size) {
        return ['success' => false, 'error' => "File is too large. Maximum size is 2MB."];
    }

    // Checks file type
    if (!in_array($imageFileType, $allowed_types)) {
        return ['success' => false, 'error' => "Only JPG, JPEG & PNG files are allowed."];
    }

    // Attempt to upload file
    if (move_uploaded_file($_FILES["candidate_image"]["tmp_name"], $target_file)) {
        return ['success' => true, 'path' => $target_file];
    } else {
        return ['success' => false, 'error' => "There was an error uploading your file."];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $form_data['name'] = trim($_POST['name'] ?? '');
    $form_data['party'] = trim($_POST['party'] ?? '');
    $form_data['position_type'] = $_POST['position_type'] ?? '';
    $form_data['manifesto'] = trim($_POST['manifesto'] ?? '');
    $form_data['status'] = isset($_POST['status']) ? 1 : 0;

    // Validate required fields
    if (empty($form_data['name'])) {
        $error = "Candidate name is required.";
    } elseif (empty($form_data['party'])) {
        $error = "Party affiliation is required.";
    } elseif ($form_data['party'] === 'new' && empty(trim($_POST['newParty'] ?? ''))) {
        $error = "Please enter a new party name.";
    } elseif (empty($form_data['position_type'])) {
        $error = "Position type is required.";
    } else {
        // Handle new party if selected
        if ($form_data['party'] === 'new') {
            $form_data['party'] = trim($_POST['newParty']);
        }

        // Handle image upload if present
        if (!empty($_FILES["candidate_image"]["name"])) {
            $upload_result = handleImageUpload();
            if ($upload_result['success']) {
                $target_file = $upload_result['path'];
                $upload_success = true;
            } else {
                $error = $upload_result['error'];
            }
        }

        // If no errors, proceed to add candidate
        if (empty($error)) {
            $image_path = $upload_success ? $target_file : null;
            
            if (addCandidate(
                $form_data['name'],
                $form_data['party'],
                $form_data['position_type'],
                $form_data['manifesto'],
                $image_path,
                $form_data['status']
            )) {
                $_SESSION['success'] = "Candidate added successfully!";
                header('Location: view_candidates.php');
                exit;
            } else {
                $error = "Failed to add candidate. Please try again.";
                // Debug information
                error_log("addCandidate failed with parameters: " . 
                    print_r([
                        'name' => $form_data['name'],
                        'party' => $form_data['party'],
                        'position_type' => $form_data['position_type'],
                        'manifesto' => $form_data['manifesto'],
                        'image_path' => $image_path,
                        'status' => $form_data['status']
                    ], true));
            }
        }
    }
}

// Get existing parties for dropdown
$parties = getExistingParties();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Candidate | E-Voting Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">


    
    <style>
        .form-card {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            background: #fff;
        }

        .preview-image {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 0.25rem;
            object-fit: cover;
            display: none;
            border: 1px solid #dee2e6;
        }

        .image-upload-container {
            border: 2px dashed #dee2e6;
            border-radius: 0.25rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .image-upload-container:hover {
            border-color: #0d6efd;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #0d6efd;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .form-group.required label:after {
            content: " *";
            color: #dc3545;
        }

        #manifesto {
            min-height: 150px;
        }
    </style>
</head>

<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php">
                <i class="fas fa-vote-yea me-2"></i>E-Voting System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_candidates.php">
                            <i class="fas fa-users me-1"></i>View Candidates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="add_candidates.php">
                            <i class="fas fa-user-plus me-1"></i>Add Candidate
                        </a>
                    </li>
                    <li><span class="nav-link disabled d-none d-lg-inline">|</span></li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="?logout=1">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<!--candiates section-->


    <main class="container my-5">
        <div class="form-card">
            <h2 class="mb-4 text-center"><i class="fas fa-user-plus me-2"></i>Add New Candidate</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="addCandidateForm" novalidate>
                <div class="form-group required mb-3">
                    <label for="position_type" class="form-label"><i class="fas fa-user-tag me-2"></i>Position</label>
                    <select class="form-select" id="position_type" name="position_type" required>
                        <option value="">SELECT POSITION</option>
                        <option value="President" <?php echo ($form_data['position_type'] === 'President') ? 'selected' : ''; ?>>President</option>
                        <option value="Governor" <?php echo ($form_data['position_type'] === 'Governor') ? 'selected' : ''; ?>>Governor</option>
                        <option value="Senator" <?php echo ($form_data['position_type'] === 'Senator') ? 'selected' : ''; ?>>Senator</option>
                        <option value="MP" <?php echo ($form_data['position_type'] === 'MP') ? 'selected' : ''; ?>>Member of Parliament</option>
                        <option value="MCA" <?php echo ($form_data['position_type'] === 'MCA') ? 'selected' : ''; ?>>Member of County Assembly</option>
                        <option value="Women Rep" <?php echo ($form_data['position_type'] === 'Women Rep') ? 'selected' : ''; ?>>Women representative</option>
                    </select>
                    <div class="invalid-feedback">Please select a position.</div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group required">
                            <label for="name" class="form-label"><i class="fas fa-user me-2"></i>Full Name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?php echo htmlspecialchars($form_data['name']); ?>" required>
                            <div class="invalid-feedback">Please enter the candidate's full name.</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group required">
                            <label for="party" class="form-label"><i class="fas fa-flag me-2"></i>Party</label>
                            <select class="form-select" id="party" name="party" required>
                                <option value="">Select Party</option>
                                <?php if (!empty($parties)): ?>
                                    <?php foreach ($parties as $party): ?>
                                        <option value="<?php echo htmlspecialchars($party); ?>"
                                            <?php echo ($form_data['party'] === $party) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($party); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <option value="new" <?php echo ($form_data['party'] === 'new') ? 'selected' : ''; ?>>+ Add New Party</option>
                            </select>
                            <div id="newPartyField" class="mt-2" style="display: <?php echo ($form_data['party'] === 'new') ? 'block' : 'none'; ?>;">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="newParty" name="newParty"
                                        placeholder="Enter new party name"
                                        value="<?php echo ($form_data['party'] === 'new') ? htmlspecialchars($_POST['newParty'] ?? '') : ''; ?>">
                                    <button type="button" class="btn btn-primary" onclick="addNewParty()">
                                        <i class="fas fa-plus me-1"></i>Add
                                    </button>
                                </div>
                            </div>
                            <div class="invalid-feedback">Please select or add a party.</div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-image me-2"></i>Candidate Photo</label>
                            <div class="image-upload-container" onclick="document.getElementById('candidate_image').click()">
                                <div id="uploadPrompt">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-muted"></i>
                                    <p class="mb-1">Click to upload candidate photo</p>
                                    <p class="small text-muted">JPG, PNG (Max 2MB)</p>
                                </div>
                                <img id="imagePreview" src="#" alt="Preview" class="preview-image">
                                <input type="file" id="candidate_image" name="candidate_image"
                                    accept="image/*" onchange="previewImage(this)" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-toggle-on me-2"></i>Candidate Status</label>
                            <div class="d-flex align-items-center mt-2">
                                <label class="toggle-switch me-3">
                                    <input type="checkbox" name="status" <?php echo $form_data['status'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span id="statusText"><?php echo $form_data['status'] ? 'Active' : 'Inactive'; ?></span>
                            </div>
                            <small class="text-muted">Toggle to activate/deactivate candidate</small>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-group">
                        <label for="manifesto" class="form-label"><i class="fas fa-file-alt me-2"></i>Manifesto/Bio</label>
                        <textarea class="form-control" name="manifesto" id="manifesto" rows="6"
                            placeholder="Enter the candidate's manifesto or biography..."><?php echo htmlspecialchars($form_data['manifesto']); ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="view_candidates.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Candidates
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Candidate
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-vote-yea me-2"></i>E-Voting System</h5>
                    <p class="mb-0">Secure and transparent voting platform for modern elections.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> E-Voting System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview image before upload
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const prompt = document.getElementById('uploadPrompt');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    prompt.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Handle new party option
        document.getElementById('party').addEventListener('change', function() {
            const newPartyField = document.getElementById('newPartyField');
            newPartyField.style.display = this.value === 'new' ? 'block' : 'none';

            // Reset new party input when switching away
            if (this.value !== 'new') {
                document.getElementById('newParty').value = '';
            }
        });

        // Add new party to dropdown
        function addNewParty() {
            const newPartyInput = document.getElementById('newParty');
            const newParty = newPartyInput.value.trim();

            if (newParty) {
                const select = document.getElementById('party');
                const option = document.createElement('option');
                option.value = option.textContent = newParty;
                option.selected = true;

                // Insert before the "Add New Party" option
                select.insertBefore(option, select.lastChild);
                document.getElementById('newPartyField').style.display = 'none';
                newPartyInput.value = '';
            } else {
                alert('Please enter a party name');
            }
        }

        // Update status text when toggle changes
        document.querySelector('input[name="status"]').addEventListener('change', function() {
            document.getElementById('statusText').textContent = this.checked ? 'Active' : 'Inactive';
        });

        // Form validation
        (function() {
            'use strict';

            const form = document.getElementById('addCandidateForm');

            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                // Custom validation for party selection
                const partySelect = document.getElementById('party');
                if (partySelect.value === 'new' && !document.getElementById('newParty').value.trim()) {
                    alert('Please enter a new party name or select an existing party');
                    event.preventDefault();
                    return false;
                }

                form.classList.add('was-validated');
            }, false);
        })();
    </script>
</body>

</html>