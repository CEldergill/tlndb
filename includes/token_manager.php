<?php

function refreshAccessToken($client_id, $client_secret)
{
    if (isset($_COOKIE['refresh_token'])) {

        $refresh_token = $_COOKIE['refresh_token'];
        // Obtain a new access token
        $token_url = "https://apis.roblox.com/oauth/v1/token";
        $post_data = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
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
            $_SESSION['access']['token'] = $response_data['access_token'];
            $_SESSION['access']['expiry'] = time() + $response_data['expires_in'];

            $refresh_token = $response_data['refresh_token'];

            // Update refresh token
            setcookie('refresh_token', $refresh_token, [
                'expires' => time() + (7 * 24 * 60 * 60), // 7 days
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            return true;
        } else {
            return false; // Failed to refresh token
        }
    }
    return false; // No refresh token available
}
