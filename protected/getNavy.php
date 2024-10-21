<?php

function getNavyMembers($group_id)
{
    $all_members = [];
    $next_page_token = "";

    if ($group_id === 2845412) {
        $navy_choice = 0;
    } else {
        $navy_choice = 1;
    }

    $roles = [
        // Nova Balreska
        [
            [19300797, "Crewman"],
            [19300794, "Specialist"],
            [96490724, "Midshipman"],
            [48774876, "Lieutenant"],
            [19300793, "Commander"],
            [20941943, "Captain"],
            [48774916, "Commodore"],
            [19300792, "Vice Admiral"],
            [48774835, "Admiral"],
            [19300786, "GSL"]
        ],
        // Whitecrest
        [
            [17181336, "Crewman"],
            [18035409, "Specialist"],
            [96490704, "Midshipman"],
            [17181412, "Lieutenant"],
            [17178532, "Commander"],
            [18039682, "Captain"],
            [48660080, "Commodore"],
            [17213973, "Vice Admiral"],
            [48772489, "Admiral"],
            [17154517, "King"],
            [17129607, "Grand Sea Lord"]
        ]
    ];

    $role_lookup = [];
    foreach ($roles as $role_set) {
        foreach ($role_set as $role) {
            $role_lookup[$role[0]] = $role[1];  // Map roleId to role name
        }
    }

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    foreach ($roles[$navy_choice][0] as $roleid) {
        $next_page_token = "";
        do {
            // API URL to fetch group members, limiting to 100 members per request. Starts at highest rank.
            $members_url = "https://groups.roblox.com/v1/groups/$group_id/roles/$roleid/users?limit=100&cursor=$next_page_token";
            curl_setopt($ch, CURLOPT_URL, $members_url);
            echo "apiCall ";

            // Execute the request and handle errors
            $members_response = curl_exec($ch);
            if ($members_response === false) {
                error_log('Curl error: ' . curl_error($ch));
                curl_close($ch);
                return false;
            }

            // Decode the JSON response
            $members_data = json_decode($members_response, true);

            // Handle JSON decoding errors
            if ($members_data === null) {
                error_log('Error decoding JSON: ' . json_last_error_msg());
                curl_close($ch);
                return false;
            }

            // Add role name to each member
            $fetched_members = array_map(function ($member) use ($role_lookup) {
                $member['role'] = isset($role_lookup[$roleId]) ? $role_lookup[$roleId] : 'Unknown'; // Add the role name
                return $member;
            }, $members_data['data'] ?? []);

            // Merge the retrieved members data
            $all_members = array_merge($all_members, $fetched_members ?? []);

            // Set the cursor for pagination
            $next_page_token = $members_data['nextPageCursor'] ?? "";
            // Ends if a citizen is found.
        } while (!empty($next_page_token));
    }
    curl_close($ch);  // Close the cURL handle

    $user_list = [];

    print_r($all_members);

    $user_list = array_map(function ($membership) {
        return [(int)$membership['userId'], $membership['username'], $membership['role']];
    }, $all_members);

    return !empty($user_list) ? $user_list : false;
}
