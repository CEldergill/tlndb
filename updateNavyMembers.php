<?php

$cron_secret = getenv('CRON_SECRET');

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    // If the key is invalid or not present, return a 403 Forbidden response
    header('HTTP/1.0 403 Forbidden');
    echo 'Access Denied';
    exit;
}

require("protected/getNavy.php");
require("protected/getNavyFromDb.php");
require("protected/pfp.php");
require("../includes/db.php");

$rank_mapping = [
    'Citizen' => 1,
    'Crewman' => 2,
    'Able Crewman' => 3,
    'Specialist' => 4,
    'Midshipman' => 5,
    'Lieutenant' => 6,
    'Commander' => 7,
    'Captain' => 8,
    'Commodore' => 9,
    'Vice Admiral' => 10,
    'Admiral' => 11,
    'Grand Sea Lord' => 12,
    'King' => 13
];

for ($selected_navy = 1; $selected_navy <= 2; $selected_navy++) {

    //NBN
    if ($selected_navy === 1) {
        $group_id = 2845412;
        $faction_id = 1;
        $citizen_id = 19300757;
    } else { // WCN
        $group_id = 2587871;
        $faction_id = 2;
        $citizen_id = 17129601;
    }

    // holds all user data from group.
    $navyFromGroup = getNavyMembers($group_id);
    // Takes just user id
    $navyGroupMembers[] = "";
    foreach ($navyFromGroup as $user) {
        $navyGroupMembers[] = $user[0];
    }
    // Takes user id & rank
    $navyGroupMembersRank[] = "";
    foreach ($navyFromGroup as $user) {
        $rank_id = isset($rank_mapping[$user[2]]) ? $rank_mapping[$user[2]] : null;
        $navyGroupMembersRank[] = [$user[0], $rank_id];
    }

    // Find users that aren't in the navy but have previously been in a navy
    $citizensFromDb = citizenFromDb($conn, $faction_id, $citizen_id);

    // Current navy members & rank id
    $navyFromDb = navyFromDb($conn, $faction_id, $citizen_id);

    $navyDbMembers[] = "";
    foreach ($navyFromDb as $user) {
        $navyDbMembers[] = $user[0];
    }

    // Check if $navyGroupMembers is not empty and $navyDbMembers is not empty
    if (!empty($navyGroupMembers) && !empty($navyDbMembers)) {
        // Find values in $group that are not in $db (ADD)
        $usersToAdd = array_diff($navyGroupMembers, $navyDbMembers);
    } else {
        $usersToAdd = NULL;
    }

    // Check if $navyGroupMembers is not empty and $citizensFromDb is not empty
    if (!empty($navyGroupMembers) && !empty($citizensFromDb)) {
        // Values in $group that are not in navy (CITIZEN -> CREWMAN)
        $usersToAmmend = array_diff($navyGroupMembers, $citizensFromDb);
    } else {
        $usersToAmmend = NULL;
    }

    // Check if $navyDbMembers is not empty and $navyGroupMembers is not empty
    if (!empty($navyDbMembers) && !empty($navyGroupMembers)) {
        // Find values in $db that are not in $group (REMOVE)
        $usersToRemove = array_diff($navyDbMembers, $navyGroupMembers);
    } else {
        $usersToRemove = NULL;
    }

    // ADDING NEW USERS

    if ($usersToAdd !== NULL) {
        // Returns PFP & User ID for new users
        (array)$userArray = getPfp($usersToAdd);

        // Associative array. User ID is key.
        $groupMembersAssoc = [];
        foreach ($navyFromGroup as $user) {
            $groupMembersAssoc[$user[0]] = [$user[1], $user[2]];
        }

        // By Ref adds user details to array w/ pfp
        foreach ($userArray as &$navyMember) {
            if (!is_array($navyMember) || empty($navyMember) || !isset($navyMember[0])) {
                continue;
            }
            $userId = $navyMember[0];
            if (isset($groupMembersAssoc[$userId])) {
                $navyMember = array_merge($navyMember, $groupMembersAssoc[$userId]);
            }
        }

        foreach ($userArray as $user) {
            if (!is_array($user) || empty($user) || !isset($user[0]) || !isset($user[1]) || !isset($user[2]) || !isset($user[3])) {
                continue;
            }
            $userid = $user[0];
            $pfp = $user[1];
            $username = $user[2];
            $rank = $user[3];
            $rank_id = isset($rank_mapping[$rank]) ? $rank_mapping[$rank] : null;

            $insert_sql = "INSERT INTO members (id, username, rank_id, image_link, faction_id) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("isisi", $userid, $username, $rank_id, $pfp, $faction_id);
            $insert_stmt->execute();


            $effectiveDate = date("Y-m-d H:i:s");
            $insert_rank_sql = "INSERT INTO rank_history (member_id, rank_id, effective_date) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_rank_sql);
            $insert_stmt->bind_param("iis", $userid, $rank_id, $effectiveDate);
            $insert_stmt->execute();
        }
    }

    // UPDATING MEMBERS 

    if (!empty($navyGroupMembersRank) && !empty($navyFromDb)) {
        $usersToUpdate = [];

        // associative array from $navyFromDb for easier lookup by user_id
        $navyFromDbAssoc = [];
        foreach ($navyFromDb as $dbUser) {
            $navyFromDbAssoc[$dbUser[0]] = $dbUser[1]; // $dbUser[0] = user_id, $dbUser[1] = rank_id
        }

        // Compare ranks in $navyGroupMembersRank with those in $navyFromDbAssoc
        foreach ($navyGroupMembersRank as $groupUser) {
            if (!is_array($groupUser) || empty($groupUser) || !isset($groupUser[0]) || !isset($groupUser[1])) {
                continue;
            }
            $userId = $groupUser[0];
            $groupRankId = $groupUser[1];

            // Check if the user exists in $navyFromDbAssoc
            if (isset($navyFromDbAssoc[$userId])) {
                $dbRankId = $navyFromDbAssoc[$userId];

                // If the rank_id is different, update it
                if ($groupRankId != $dbRankId) {
                    $usersToUpdate[] = [
                        'user_id' => $userId,
                        'new_rank_id' => $groupRankId
                    ];
                }
            }
        }
    } else {
        $usersToUpdate = NULL;
    }

    // adding users who are already on the db that are ranked citizen
    if (!empty($usersToAmmend)) {
        foreach ($usersToAmmend as $users) {
            $usersRankToAmend[] = [
                'user_id' => $userId,
                'new_rank_id' => 2 //crewman
            ];
        }
    } else {
        $usersRankToAmend = NULL;
    }
    // citizen -> crewman
    if ($usersRankToAmend !== NULL && !empty($usersRankToAmend)) {
        $stmt = $conn->prepare("UPDATE members SET rank_id = ?, join_date = ?, promotion_date = NULL WHERE id = ?");

        // Loop through each user that needs an update
        foreach ($usersRankToAmend as $user) {
            $joinDate = date("Y-m-d H:i:s");
            // Bind the parameters and execute the update for each user
            $stmt->bind_param("isi", $user['new_rank_id'], $joinDate, $user['user_id']);
            $stmt->execute();

            $insert_rank_sql = "INSERT INTO rank_history (member_id, rank_id, effective_date) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_rank_sql);
            $insert_stmt->bind_param("iis", $user['user_id'], $user['new_rank_id'], $joinDate);
            $insert_stmt->execute();
        }
    }

    // updates ranks
    if ($usersToUpdate !== NULL && !empty($usersToUpdate)) {
        $stmt = $conn->prepare("UPDATE members SET rank_id = ?, promotion_date = ? WHERE id = ?");

        // Loop through each user that needs an update
        foreach ($usersToUpdate as $user) {
            $promotionDate = date("Y-m-d H:i:s");
            // Bind the parameters and execute the update for each user
            $stmt->bind_param("isi", $user['new_rank_id'], $promotionDate, $user['user_id']);
            $stmt->execute();

            $insert_rank_sql = "INSERT INTO rank_history (member_id, rank_id, effective_date) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_rank_sql);
            $insert_stmt->bind_param("iis", $user['user_id'], $user['new_rank_id'], $promotionDate);
            $insert_stmt->execute();
        }
    }

    // REMOVING MEMBERS

    if ($usersToRemove !== NULL && !empty($usersToRemove)) {
        $stmt = $conn->prepare("UPDATE members SET rank_id = 1, join_date = NULL, promotion_date = NULL WHERE id = ?");
        foreach ($usersToRemove as $user) {
            $effectiveDate = date("Y-m-d H:i:s");
            // Bind the parameters and execute the update for each user
            $stmt->bind_param("i", $user);
            $stmt->execute();

            $insert_rank_sql = "INSERT INTO rank_history (member_id, rank_id, effective_date) VALUES (?, 1, ?)";
            $insert_stmt = $conn->prepare($insert_rank_sql);
            $insert_stmt->bind_param("is", $user, $effectiveDate);
            $insert_stmt->execute();
        }
    }
}
