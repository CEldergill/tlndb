<?php
session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = []; // Initialize if not set
}

$user = $_SESSION['user'];

if (isset($_POST['selected_navy'])) {
    $user['selected_navy'] = $_POST['selected_navy']; // Store the selected option in session
}

$_SESSION['user'] = $user;
