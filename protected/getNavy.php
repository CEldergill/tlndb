<?php
function getNavyMembers($group_id)
{
    $all_members = ['users' => []];
    $next_page_token = "";
    do {
        $members_url = "https://groups.roblox.com/v1/groups/$group_id/users?limit=100&sortOrder=Asc&cursor=$next_page_token"; // MAX EXECUTION TIME 120s
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $members_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $members_response = curl_exec($ch);

        if ($members_response === false) {
            echo 'Curl error: ' . curl_error($ch);
            exit();
        }

        $members_data = json_decode($members_response, true);

        if ($members_data === null) {
            echo 'Error decoding JSON: ' . json_last_error_msg();
            exit();
        }

        $all_members['users'] = array_merge($all_members['users'], $members_data['data'] ?? []);

        $next_page_token = $members_data['nextPageCursor'] ?? "";
    } while (!empty($next_page_token));

    $user_list = [];

    if (!empty($all_members['users'])) {
        foreach ($all_members['users'] as $membership) {
            $role = $membership['role']['name'];
            $username = $membership['user']['username'];
            $user_id = (int)$membership['user']['userId'];

            if ($role !== "Citizen" && $role !== "Subject" && $role !== "Moderator" && $role !== "Nahr") {
                $user_list[] = [$user_id, $username, $role];
            }
        }
    }
    if ($user_list) {
        return ($user_list);
    } else {
        return false;
    }
};
