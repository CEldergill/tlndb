<?php
$client_id = getenv('CLIENT_ID');
$redirect_uri = 'https://www.tlndb.remote.ac/callback';
$state = bin2hex(random_bytes(16)); // CSRF protection

// Redirect to Roblox OAuth 2.0 authorization endpoint
$scopes = "openid profile group:read";
$oauth_url = "https://apis.roblox.com/oauth/v1/authorize?client_id=$client_id&response_type=code&redirect_uri=" . urlencode($redirect_uri) . "&scope=" . urldecode($scopes) . "&state=$state";

header("Location: $oauth_url");
exit();
