<?php

session_start();

$client_secret = getenv('CLIENT_SECRET');
$client_id = getenv('CLIENT_ID');
$redirect_uri = 'https://www.tlndb.remote.ac/callback';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $token_url = "https://apis.roblox.com/oauth/v1/token";
    $post_data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($response, true);

    if (isset($response_data['access_token'])) {
        $access_token = $response_data['access_token'];
        $access_token_expiry = $response_data['expires_in'];

        $_SESSION['access']['token'] = $access_token;
        $_SESSION['access']['expiry'] = time() + $access_token_expiry;

        $refresh_token = $response_data['refresh_token'];
        setcookie('refresh_token', $refresh_token, [
            'expires' => time() + (7 * 24 * 60 * 60), // 7 days
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Use Access Token to Get User Info
        $user_info_url = "https://apis.roblox.com/oauth/v1/userinfo";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $user_info_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $user_response = curl_exec($ch);
        curl_close($ch);

        $user_data = json_decode($user_response, true);

        if (!isset($_SESSION['user']['selected_navy'])) {
            $_SESSION['error'] = "Error: Unable to get access token.";
            header("Location: index");
        }

        $_SESSION['user'] = array_merge($_SESSION['user'], $user_data);

        header("Location: groupcheck");
        exit();
    } else {
        $_SESSION['error'] = "Error: Unable to get access token.";
        header("Location: index");
        exit();
    }
} else {
    $_SESSION['error'] = "Error: No authorization code provided.";
    header("Location: index");
    exit();
}
