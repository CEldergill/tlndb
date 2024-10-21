<?php
session_start();
if (isset($_SESSION['user'])) {
    require 'includes/token_manager.php';

    $client_secret = getenv('CLIENT_SECRET');
    $client_id = getenv('CLIENT_ID');

    if (isset($_SESSION['access']['expiry']) && time() >= ($_SESSION['access']['expiry'] - 300)) {
        if (!refreshAccessToken($client_id, $client_secret)) {
            $_SESSION['error'] = "Error: Unable to refresh access token.";
            header("Location: index");
            exit();
        }
    }
    $auth = true;
} else {
    $auth = false;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Tradelands Navy Database</title>
    <link rel="icon" href="assets/TLLOGO.png"
        type="image/x-icon" />
</head>

<body mx-2>
    <?php if ($auth) {
        include_once("components/nav.php");
    } else { ?>
        <nav class="d-flex flex-wrap justify-content-between align-items-center py-3 px-3 bg-dark text-light">
            <!-- Logos -->
            <div class="d-flex align-items-center me-auto">
                <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-5 text-decoration-none">
                    <img src="assets/logo-white.png" alt="Tradelands" width="204" height="60">
                </a>
            </div>

            <!-- Navigation Options -->
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="index" class="nav-link btn btn-primary">Login</a></li>
            </ul>

            <!-- Profile Picture -->
            <div class="profile-picture ms-3">
                <img src="assets/default-profile.png" alt="Profile" width="48" height="48" class="rounded-circle">
            </div>
        </nav>
    <?php } ?>

    <div class="container mx-3 my-3">
        <h1>Privacy Policy</h1>
        <p><strong>Last updated:</strong> 20th October 2024</p>
        <hr>

        <h2>1. Information We Collect</h2>
        <p>When you log in to the Website using your Roblox account via OAuth 2.0, we collect the following information from your Roblox account:</p>
        <ul>
            <li>Roblox user ID</li>
            <li>Roblox username</li>
            <li>Roblox Group Memberships</li>
        </ul>
        <p>We do not collect any other personal information unless voluntarily provided by the user.</p>

        <h2>2. How We Use Your Information</h2>
        <p>We use the information collected for the following purposes:</p>
        <ul>
            <li>To provide access to the Website and its services;</li>
            <li>To customize and improve the user experience on the Website;</li>
            <li>To ensure the security of your account and the Website.</li>
        </ul>

        <h2>3. Data Storage and Security</h2>
        <p>- Your Roblox user ID and username are stored securely in our database.</p>
        <p>- We implement appropriate technical and organizational measures to protect your information from unauthorized access, disclosure, or misuse.</p>
        <p>- We do not sell, rent, or share your information with third parties unless required by law.</p>

        <h2>4. Third-Party Services</h2>
        <p>The Website integrates with Roblox's OAuth 2.0 login system to authenticate users. By using the Website, you acknowledge and agree that your information is shared with Roblox in accordance with Roblox's own Privacy Policy.</p>

        <h2>5. Your Rights</h2>
        <p>You have the right to:</p>
        <ul>
            <li>Request access to the information we have collected about you;</li>
            <li>Request correction or deletion of your information;</li>
            <li>Withdraw consent for the use of your data at any time.</li>
        </ul>
        <p>To exercise any of these rights, please contact us at cjegamesrb@gmail.com.</p>

        <h2>6. Changes to This Privacy Policy</h2>
        <p>We may update this Privacy Policy from time to time. Any changes will be posted on this page with an updated revision date.</p>

        <h2>7. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at cjegamesrb@gmail.com.</p>
    </div>


    <?php include_once("components/footer.html"); ?>

</body>

</html>