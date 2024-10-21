<?php
function getPfp($array)
{
    if (empty($array)) {
        return [];
    }

    // Break the array into chunks of 100
    $chunks = array_chunk($array, 100);
    $pfpArray = [];

    // Loop over each chunk and make the request
    foreach ($chunks as $chunk) {
        // Convert the chunk array to a comma-separated list of user IDs
        $userIds = implode(',', $chunk);

        // Batch request to fetch multiple profile pictures at once
        $pfp_url = "https://thumbnails.roblox.com/v1/users/avatar-headshot?userIds=$userIds&size=420x420&format=Png&isCircular=false";

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pfp_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $pfp_response = curl_exec($ch);

        if ($pfp_response === false) {
            echo 'Curl error: ' . curl_error($ch);
            curl_close($ch);
            continue; // Skip this chunk and continue with the next
        }

        // Decode the JSON response
        $pfp_data = json_decode($pfp_response, true);

        if ($pfp_data === null) {
            echo 'Error decoding JSON: ' . json_last_error_msg();
            curl_close($ch);
            continue; // Skip this chunk and continue with the next
        }

        // Loop through the data and build the result array
        if (!empty($pfp_data['data'])) {
            foreach ($pfp_data['data'] as $item) {
                $pfpArray[] = [
                    'id' => $item['targetId'],
                    'imageUrl' => $item['imageUrl'] ?? null
                ];
            }
        }

        // Close cURL for this iteration
        curl_close($ch);
    }
    return $pfpArray;
}
