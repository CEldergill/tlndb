<?php
function getPfp($array)
{
    $pfpArray[] = "";
    foreach ($array as $id) {
        $id = strval($id);
        $pfp_url = "https://thumbnails.roblox.com/v1/users/avatar-headshot?userIds=$id&size=420x420&format=Png&isCircular=false";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pfp_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $pfp_response = curl_exec($ch);

        if ($pfp_response === false) {
            echo 'Curl error: ' . curl_error($ch);
        }

        // Process the body as JSON
        $pfp_data = json_decode($pfp_response, true);

        if ($pfp_data === null) {
            echo 'Error decoding JSON: ' . json_last_error_msg();
        }

        // Verify that data exists in the response
        if (!empty($pfp_data['data'][0]['imageUrl'])) {
            $pfp_link = $pfp_data['data'][0]['imageUrl'];
        } else {
            $pfp_link = NULL;
        }
        $pfpArray[] = [$id, $pfp_link];

        curl_close($ch);
    }
    return $pfpArray;
}
