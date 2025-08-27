<?php
// Start session
session_start();

// Popup message and redirect
echo "<script>
    alert('Admin cannot add voter. Go to login page');
    window.location.href = 'login.php';
</script>";
exit();
?>
