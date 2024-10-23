<?php
if (!isset($_SESSION['access']) || !isset($_SESSION['user'])) {
    $_SESSION['error'] = "Not authenticated. Please retry.";
    header("Location: index.php");
    exit();
}
