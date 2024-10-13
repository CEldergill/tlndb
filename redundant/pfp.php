
<?php
$pfp_url = "https://apis.roblox.com/cloud/v2/users/$id:generateThumbnail?size=420&format=PNG&shape=ROUND";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $pfp_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$pfp_response = curl_exec($ch);

if ($pfp_response === false) {
    $_SESSION['error'] = 'Curl error: ' . curl_error($ch);
    header("Location: index.php");
    exit();
}

$pfp_data = json_decode($pfp_response, true);

if ($pfp_data === null) {
    $_SESSION['error'] = 'Error decoding JSON: ' . json_last_error_msg();
    header("Location: index.php");
    exit();
}

$pfp_link = $pfp_data['response']['imageUri'];

if (empty($pfp_data['response']['imageUri'])) {
    $_SESSION['error'] = 'Session expired - Please reload.';
    header("Location: index.php");
    exit();
}

curl_close($ch);

?>