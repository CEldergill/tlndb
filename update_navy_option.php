<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedNavy = isset($_POST['selectedNavy']) ? $_POST['selectedNavy'] : null;
    $_SESSION['user']['selected_navy'] = $selectedNavy;
}
