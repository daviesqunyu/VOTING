<?php
/**
 * E-Voting System Error Checker
 * This script checks for common errors and issues in the system
 */

// Start session
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>E-Voting System Error Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .check { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        h1, h2 { color: #333; }
        .summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔍 E-Voting System Error Check</h1>
    <p>Checking for common errors and issues...</p>";

$errors = [];
$warnings = [];
$successes = [];

// 1. Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    $successes[] = "PHP version: " . PHP_VERSION . " ✓";
} else {
    $errors[] = "PHP version " . PHP_VERSION . " is too old. Recommended: 7.4+";
}

// 2. Check required PHP extensions
$required_extensions = ['mysqli', 'session', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        $successes[] = "PHP extension '$ext' is loaded ✓";
    } else {
        $errors[] = "PHP extension '$ext' is not loaded";
    }
}

// 3. Check database connection
try {
    $db = mysqli_connect('localhost', 'root', '', 'multi-login');
    if ($db) {
        $successes[] = "Database connection successful ✓";
        mysqli_close($db);
    } else {
        $errors[] = "Database connection failed: " . mysqli_connect_error();
    }
} catch (Exception $e) {
    $errors[] = "Database connection error: " . $e->getMessage();
}

// 4. Check required files
$required_files = [
    'functions.php',
    'index.php',
    'login.php',
    'register.php',
    'vote.php',
    'results.php',
    'admin_dashboard.php',
    'add_candidates.php',
    'add_voter.php',
    'remove_voter.php',
    'my_votes.php',
    'logout.php',
    'css/style.css'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        $successes[] = "File '$file' exists ✓";
    } else {
        $errors[] = "Required file '$file' is missing";
    }
}

// 5. Check file permissions
$writable_dirs = ['uploads', 'uploads/candidates'];
foreach ($writable_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            $successes[] = "Directory '$dir' is writable ✓";
        } else {
            $warnings[] = "Directory '$dir' is not writable";
        }
    } else {
        $warnings[] = "Directory '$dir' does not exist";
    }
}

// 6. Check for syntax errors in PHP files
$php_files = [
    'functions.php',
    'index.php',
    'login.php',
    'register.php',
    'vote.php',
    'results.php',
    'admin_dashboard.php',
    'add_candidates.php',
    'add_voter.php',
    'remove_voter.php',
    'my_votes.php',
    'logout.php'
];

foreach ($php_files as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            $successes[] = "PHP syntax check passed for '$file' ✓";
        } else {
            $errors[] = "PHP syntax error in '$file': " . trim($output);
        }
    }
}

// 7. Check for common security issues
$security_checks = [
    'functions.php' => ['password_hash', 'mysqli_real_escape_string', 'htmlspecialchars'],
    'login.php' => ['htmlspecialchars', 'password_verify'],
    'register.php' => ['htmlspecialchars', 'password_hash']
];

foreach ($security_checks as $file => $functions) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        foreach ($functions as $func) {
            if (strpos($content, $func) !== false) {
                $successes[] = "Security function '$func' found in '$file' ✓";
            } else {
                $warnings[] = "Security function '$func' not found in '$file'";
            }
        }
    }
}

// 8. Check for Bootstrap version consistency
$bootstrap_versions = [];
foreach ($php_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match('/bootstrap@([\d.]+)/', $content, $matches)) {
            $bootstrap_versions[] = $matches[1];
        }
    }
}

if (count(array_unique($bootstrap_versions)) === 1) {
    $successes[] = "Bootstrap version consistent: " . $bootstrap_versions[0] . " ✓";
} else {
    $warnings[] = "Inconsistent Bootstrap versions found: " . implode(', ', array_unique($bootstrap_versions));
}

// 9. Check for common HTML issues
foreach ($php_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for unclosed HTML tags
        if (strpos($content, '<html') !== false && strpos($content, '</html>') === false) {
            $warnings[] = "Unclosed HTML tag in '$file'";
        }
        
        // Check for proper DOCTYPE
        if (strpos($content, '<!DOCTYPE html>') === false) {
            $warnings[] = "Missing DOCTYPE declaration in '$file'";
        }
        
        // Check for proper meta charset
        if (strpos($content, 'charset="UTF-8"') === false) {
            $warnings[] = "Missing UTF-8 charset declaration in '$file'";
        }
    }
}

// Display results
echo "<div class='summary'>
    <h2>📊 Check Summary</h2>
    <p><strong>Total Checks:</strong> " . (count($successes) + count($warnings) + count($errors)) . "</p>
    <p><strong>✓ Successes:</strong> " . count($successes) . "</p>
    <p><strong>⚠ Warnings:</strong> " . count($warnings) . "</p>
    <p><strong>❌ Errors:</strong> " . count($errors) . "</p>
</div>";

// Display successes
if (!empty($successes)) {
    echo "<h2>✅ Successes</h2>";
    foreach ($successes as $success) {
        echo "<div class='check success'>$success</div>";
    }
}

// Display warnings
if (!empty($warnings)) {
    echo "<h2>⚠️ Warnings</h2>";
    foreach ($warnings as $warning) {
        echo "<div class='check warning'>$warning</div>";
    }
}

// Display errors
if (!empty($errors)) {
    echo "<h2>❌ Errors</h2>";
    foreach ($errors as $error) {
        echo "<div class='check error'>$error</div>";
    }
}

// Final status
if (empty($errors)) {
    echo "<div class='check success'>
        <h2>🎉 System Status: GOOD</h2>
        <p>No critical errors found. The system should work properly.</p>
    </div>";
} else {
    echo "<div class='check error'>
        <h2>🚨 System Status: NEEDS ATTENTION</h2>
        <p>Critical errors found. Please fix them before using the system.</p>
    </div>";
}

echo "<div class='info'>
    <h3>💡 Recommendations</h3>
    <ul>
        <li>Fix any errors before using the system</li>
        <li>Address warnings to improve system quality</li>
        <li>Test all functionality after making changes</li>
        <li>Keep backups of your database and files</li>
        <li>Regularly update your system and dependencies</li>
    </ul>
</div>

</body>
</html>";
?> 