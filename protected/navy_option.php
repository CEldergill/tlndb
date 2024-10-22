<?php
session_start();

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo "Access denied";
    exit;
}
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = []; // Initialize if not set
}

$user = $_SESSION['user'];

if (isset($_POST['selected_navy'])) {
    $user['selected_navy'] = $_POST['selected_navy']; // Store the selected option in session
}

$_SESSION['user'] = $user;
