<?php
session_start();
$activePage = 'log_event';
require 'includes/token_manager.php';
require 'includes/db.php';

if (isset($_SESSION['access']['expiry']) && time() >= ($_SESSION['access']['expiry'] - 300)) {
    if (!refreshAccessToken()) {
        $_SESSION['error'] = "Error: Unable to refresh access token.";
        header("Location: index.php");
        exit();
    }
}

#data
$token = $_SESSION['access']['token'];
$user = $_SESSION['user'];
$selected_navy = $user['selected_navy'];
$id = $user['sub'];
$displayName = $user['name'];
$userName = $user['preferred_username'];
$rank = $user['rank'];


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $selected_navy ?> Event Log</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>


<body>
    <?php include_once("components/nav.php"); ?>


</body