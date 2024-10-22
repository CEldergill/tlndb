<?php

session_start();

require 'includes/db.php';

if (!isset($_SESSION['user'])) {
    // If no user data is found in the session, redirect to the login page
    $_SESSION['error'] = "No user data retrieved. Please retry.";
    header("Location: index");
    exit();
}

#data
$token = $_SESSION['access']['token'];

$user = $_SESSION['user'];
$id = $user['sub'];
$selected_navy = $user['selected_navy'];

$effectiveDate = date("Y-m-d H:i:s");

$nova_roles = [
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

$whitecrest_roles = [
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

function find_role_name($roles, $id)
{
    foreach ($roles as $role) {
        if ($role[0] === $id) {
            return $role[1]; // Return the name if found
        }
    }
    return null; // Return null if not found
}


#check navy selection

if ($selected_navy === "NBN") {
    $group_id = 2845412;
    $citizen_id = 19300757;
} else if ($selected_navy === "WCN") {
    $group_id = 2587871;
    $citizen_id = 17129601;
} else {
    $_SESSION['error'] = "Group selection error. Please contact an adminstrator. " . $selected_navy;
    header("Location: index");
    exit();
}

#Checks the user is in the navy.

$filter = "user == 'users/$id'";
$encoded_filter = urlencode($filter);

$group_url = "https://apis.roblox.com/cloud/v2/groups/$group_id/memberships?maxPageSize=10&filter=$encoded_filter";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $group_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$group_response = curl_exec($ch);

if ($group_response === false) {
    $_SESSION['error'] = 'Curl error: ' . curl_error($ch);
    header("Location: index");
    exit();
}

$group_data = json_decode($group_response, true);

if ($group_data === null) {
    $_SESSION['error'] = 'Error decoding JSON: ' . json_last_error_msg();
    header("Location: index");
    exit();
}

$user_in_group = false; // Flag to check if user is in the group and has a navy role

if (!empty($group_data['groupMemberships'])) {
    foreach ($group_data['groupMemberships'] as $membership) {
        // Check if the user's role matches the citizen role
        $role_id = basename($membership['role']);
        $role_id = (int)$role_id;

        if ($role_id != $citizen_id) {
            $user_in_group = true; // User is in the group and has the correct role
            break;
        }
    }
}

if (!$user_in_group) {
    $_SESSION['error'] = "You must be a navy member to access this page.";
    header("Location: index");
} else {
    unset($_SESSION['error']);

    $user_id = $_SESSION['user']['sub'];

    $sql = "SELECT user_id
    FROM tlndb_users
    WHERE user_id = ?;";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id); // "i" indicates an integer
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows > 0) {
        $sql = "UPDATE tlndb_users SET last_login_date = CURRENT_TIMESTAMP() WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } else {
        $insertUser = $conn->prepare("INSERT INTO `tlndb_users`(`user_id`, `join_date`) VALUES (?,?)");
        $insertUser->bind_param("is", $user_id, $effectiveDate);
        $insertUser->execute();
    }
    header("Location: home");
}

curl_close($ch);

if ($selected_navy === "NBN") {
    $user_rank = find_role_name($nova_roles, $role_id);
} else {
    $user_rank = find_role_name($whitecrest_roles, $role_id);
}

$user['rank'] = $user_rank;

$_SESSION['user'] = $user;


exit;
