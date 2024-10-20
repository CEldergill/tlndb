<?php
session_start();
require 'includes/token_manager.php';

$client_secret = getenv('CLIENT_SECRET');
$client_id = getenv('CLIENT_ID');

if (isset($_SESSION['access']['expiry']) && time() >= ($_SESSION['access']['expiry'] - 300)) {
    if (!refreshAccessToken($client_id, $client_secret)) {
        $_SESSION['error'] = "Error: Unable to refresh access token.";
        header("Location: index.php");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tradelands Navy Database</title>
</head>

<body>
    <?php include_once("components/nav.php"); ?>

    <div class="container text-center">
        <h1>Terms and Conditions</h1>
        <p><strong>Last updated:</strong> 20th October 2024</p>
        <hr>

        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using Tradelands Navy Database (hereinafter referred to as "the Website"), you agree to comply with and be bound by these Terms and Conditions, as well as any applicable laws and regulations. If you do not agree with these terms, you are prohibited from using or accessing the Website.</p>

        <h2>2. Use of the Website</h2>
        <p>- You must be at least 13 years old to use this Website.</p>
        <p>- The Website allows users to log in via their Roblox account using OAuth 2.0. By logging in, you agree to share certain Roblox account information, such as your Roblox user ID and username.</p>
        <p>- The Website is provided on an "as is" and "as available" basis. We do not guarantee that the Website will be available at all times without interruptions.</p>

        <h2>3. User Account</h2>
        <p>- By logging in with your Roblox account, you agree that the Website will store your Roblox user ID and username for the purpose of offering its services.</p>
        <p>- You are responsible for maintaining the confidentiality of your account login information and are responsible for all activities that occur under your account.</p>

        <h2>4. Prohibited Activities</h2>
        <p>You agree not to engage in any activities that:</p>
        <ul>
            <li>Violate any laws or regulations;</li>
            <li>Interfere with or disrupt the operation of the Website;</li>
            <li>Attempt to gain unauthorized access to other users' data or accounts.</li>
        </ul>

        <h2>5. Limitation of Liability</h2>
        <p>In no event shall the Website or its owners be liable for any indirect, special, incidental, or consequential damages arising out of the use or inability to use the Website.</p>

        <h2>6. Modifications to Terms</h2>
        <p>We reserve the right to update these Terms and Conditions at any time without prior notice. The updated version will be effective as soon as it is posted on the Website.</p>

        <h2>7. Governing Law</h2>
        <p>These Terms and Conditions are governed by and construed in accordance with the laws of the United Kingdom of Great Britain and Northern Ireland.</p>
    </div>


    <?php include_once("components/footer.html"); ?>

</body>

</html>