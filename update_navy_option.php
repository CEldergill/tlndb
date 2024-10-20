<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedNavy = isset($_POST['selectedNavy']) ? $_POST['selectedNavy'] : null;
    if ($selectedNavy === 1) {
        $_SESSION['user']['selected_navy'] = "NBN";
    } else {
        $_SESSION['user']['selected_navy'] = "WCN";
    }
}
