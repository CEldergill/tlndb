<?php

function getNavyMembers($group_id)
{
    $all_members = [];
    $next_page_token = "";
    $foundCitizen = false;

    do {
        // API URL to fetch group members, limiting to 100 members per request. Starts at highest rank.
        $members_url = "https://groups.roblox.com/v1/groups/$group_id/users?limit=100&sortOrder=Desc&cursor=$next_page_token";
        echo "apiCall ";
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $members_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the request and handle errors
        $members_response = curl_exec($ch);
        if ($members_response === false) {
            error_log('Curl error: ' . curl_error($ch));  // Use error_log instead of echoing
            curl_close($ch);
            return false;
        }

        // Decode the JSON response
        $members_data = json_decode($members_response, true);
        curl_close($ch);  // Close the cURL handle

        // Handle JSON decoding errors
        if ($members_data === null) {
            error_log('Error decoding JSON: ' . json_last_error_msg());
            return false;
        }

        // Check the last role in the list to see if it is a citizen
        $last_entry = end($members_data['data']);
        print_r($last_entry);
        $last_role = $last_entry['role']['name'];
        if (in_array($last_role, ["Citizen", "Subject"])) {
            $foundCitizen = true;
        }

        // Merge the retrieved members data
        $all_members = array_merge($all_members, $members_data['data'] ?? []);

        // Set the cursor for pagination
        $next_page_token = $members_data['nextPageCursor'] ?? "";
        // Ends if a citizen is found.
    } while (!empty($next_page_token) && !$foundCitizen);

    // Filter the members by excluding certain roles
    $user_list = [];
    $excluded_roles = ['Citizen', 'Subject', 'Moderator', 'Nahr'];  // Roles to be excluded

    foreach ($all_members as $membership) {
        $role = $membership['role']['name'];
        $username = $membership['user']['username'];
        $user_id = (int)$membership['user']['userId'];

        if (!in_array($role, $excluded_roles)) {
            $user_list[] = [$user_id, $username, $role];
        }
    }

    return !empty($user_list) ? $user_list : false;
}
