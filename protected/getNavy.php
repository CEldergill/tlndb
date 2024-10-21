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
            19300797, // Crewman
            19300794,
            96490724,
            48774876,
            19300793,
            20941943,
            48774916,
            19300792,
            48774835,
            19300786 // GSL
        ],
        // Whitecrest
        [
            17181336, // Crewman
            18035409,
            96490704,
            17181412,
            17178532,
            18039682,
            48660080,
            17213973,
            48772489,
            17154517,
            17129607 // King
        ]
    ];
    $excluded_roles = ['Citizen', 'Subject', 'Moderator', 'Nahr'];  // Roles to be excluded

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    foreach ($roles[$navy_choice] as $roleid) {
        do {
            // API URL to fetch group members, limiting to 100 members per request. Starts at highest rank.
            $members_url = "https://groups.roblox.com/v1/groups/$group_id/roles/$roleid?limit=100&&cursor=$next_page_token";
            curl_setopt($ch, CURLOPT_URL, $members_url);
            echo "apiCall ";

            // Execute the request and handle errors
            $members_response = curl_exec($ch);
            if ($members_response === false) {
                error_log('Curl error: ' . curl_error($ch));  // Use error_log instead of echoing
                curl_close($ch);
                return false;
            }

            // Decode the JSON response
            $members_data = json_decode($members_response, true);

            // Handle JSON decoding errors
            if ($members_data === null) {
                error_log('Error decoding JSON: ' . json_last_error_msg());
                return false;
            }

            $fetched_members = array_map(function ($member) use ($roleid) {
                $member['roleId'] = $roleid;
                return $member;
            }, $members_data['data'] ?? []);

            // Merge the retrieved members data
            $all_members = array_merge($all_members, $fetched_members['data'] ?? []);

            // Set the cursor for pagination
            $next_page_token = $members_data['nextPageCursor'] ?? "";
            // Ends if a citizen is found.
        } while (!empty($next_page_token));
    }
    curl_close($ch);  // Close the cURL handle

    // Filter the members by excluding certain roles
    $user_list = [];

    print_r($all_members);

    // Filter out excluded roles
    $user_list = array_filter($all_members, function ($membership) use ($excluded_roles) {
        return !in_array($membership['role'], $excluded_roles);
    });

    // Transform the filtered list to the desired structure
    $user_list = array_map(function ($membership) {
        return [(int)$membership['userId'], $membership['username'], $membership['role']];
    }, $user_list);

    return !empty($user_list) ? $user_list : false;
}
