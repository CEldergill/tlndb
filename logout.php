<?php
session_start();

// Function to revoke the refresh token
function revokeRefreshToken($refresh_token)
{
    $token_url = "https://apis.roblox.com/oauth/v1/token/revoke";

    $post_data = [
        'token' => $refresh_token
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Bearer ' . $_SESSION['access']['token'] // Access token to authorize the revocation request
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true); // Return the response as an array
}

// Check if the refresh token cookie exists and revoke it
if (isset($_COOKIE['refresh_token'])) {
    $refresh_token = $_COOKIE['refresh_token'];

    // Revoke the refresh token
    $revoke_response = revokeRefreshToken($refresh_token);

    // Clear the refresh token cookie
    setcookie('refresh_token', '', time() - 3600, '/'); // Expire the cookie
}

// Destroy the session
session_destroy(); // Destroy all session data
header("Location: index.php"); // Redirect to the login page
exit();
