<?php
include('functions.php');
if (!isLoggedIn() || !isAdmin()) header('Location: login.php');

if (isset($_GET['id'])) {
  deleteCandidate((int)$_GET['id']);
  $_SESSION['success'] = "Candidate removed.";
}
header('Location: view_candidates.php');
exit();
?>