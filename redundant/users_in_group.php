<?php
session_start();

#data
$token = $_SESSION['access']['token'];
$user = $_SESSION['user'];
$selected_navy = $user['selected_navy'];
$id = $user['sub'];
$displayName = $user['name'];
$userName = $user['preferred_username'];
$rank = $user['rank'];

$nova_roles = [
    '19300797',
    '19300794',
    '96490724',
    '48774876',
    '19300793',
    '20941943',
    '48774916',
    '19300792',
    '48774835',
    '19300786',
    '19300756'
];

$wc_roles = [
    '17181336',
    '18035409',
    '96490704',
    '17181412',
    '17178532',
    '18039682',
    '48660080',
    '17213973',
    '48772489',
    '17154517',
    '17129607'
];

$nova_role_names = [
    [19300797, "Crewman"],
    [19300794, "Able Crewman"],
    [96490724, "Specialist"],
    [48774876, "Midshipman"],
    [19300793, "Lieutenant"],
    [20941943, "Commander"],
    [48774916, "Captain"],
    [19300792, "Commodore"],
    [48774835, "Vice Admiral"],
    [19300786, "Admiral"],
    [19300756, "Grand Sea Lord"],
    [19300781, "Moderator"],
    [19300755, "Nahr"]
];

$whitecrest_role_names = [
    [17181336, "Crewman"],
    [18035409, "Able Crewman"],
    [96490704, "Specialist"],
    [17181412, "Midshipman"],
    [17178532, "Lieutenant"],
    [18039682, "Commander"],
    [48660080, "Captain"],
    [17213973, "Commodore"],
    [48772489, "Vice Admiral"],
    [17154517, "Admiral"],
    [17129607, "King"],
    [17129600, "Moderator"],
    [17129599, "Nahr"]
];

function getRoleNameById($id, $roleArray)
{
    foreach ($roleArray as $role) {
        if ($role[0] == $id) {
            return $role[1];
        }
    }
    return "Role not found";
}


$all_members = ['groupMemberships' => []];

foreach ($nova_roles as $role_id) {
    if ($selected_navy === "NBN") {
        $filter = "role == 'groups/2845412/roles/" . $role_id . "'";
        $group_id = 2845412;
    } else {
        $filter = "role == 'groups/2587871/roles/" . $role_id . "'";
        $group_id = 2587871;
    }

    $encoded_filter = urlencode($filter);
    $next_page_token = "";

    do {
        $members_url = "https://apis.roblox.com/cloud/v2/groups/$group_id/memberships?maxPageSize=100&pageToken=$next_page_token&filter=$encoded_filter";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $members_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $members_response = curl_exec($ch);

        if ($members_response === false) {
            $_SESSION['error'] = 'Curl error: ' . curl_error($ch);
            header("Location: index.php");
            exit();
        }

        $members_data = json_decode($members_response, true);

        if ($members_data === null) {
            $_SESSION['error'] = 'Error decoding JSON: ' . json_last_error_msg();
            header("Location: index.php");
            exit();
        }

        $all_members['groupMemberships'] = array_merge($all_members['groupMemberships'], $members_data['groupMemberships'] ?? []);

        $next_page_token = $members_data['nextPageToken'] ?? "";
    } while (!empty($next_page_token));
}

$user_id_list = [];

if (!empty($all_members['groupMemberships'])) {
    foreach ($all_members['groupMemberships'] as $membership) {
        $role_id = basename($membership['role']);
        $role_id = (int)$role_id;
        $user_id = basename(($membership['user']));
        $user_id = (int)$user_id;
        if ($selected_navy === "NBN") {
            $role_name = getRoleNameById($role_id, $nova_role_names);
        } else {
            $role_name = getRoleNameById($role_id, $whitecrest_role_names);
        }

        array_push($user_id_list, [$user_id, $role_name]);
    }
}
print_r($user_id_list);
