<?php

$cron_secret = getenv('CRON_SECRET');

if (!isset($_GET['key']) || $_GET['key'] !== $cron_secret) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access Denied';
    exit;
}

require("protected/getNavy.php");
require("protected/getNavyFromDb.php");
require("protected/pfp.php");
require("includes/db.php");

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

//determines which navy is being requested
$navy_to_process = isset($_GET['navy']) ? strtolower($_GET['navy']) : null;

$navies = [
    'nbn' => ['group_id' => 2845412, 'faction_id' => 1, 'citizen_id' => 19300757], // NBN
    'wcn' => ['group_id' => 2587871, 'faction_id' => 2, 'citizen_id' => 17129601]  // WCN
];

if ($navy_to_process && isset($navies[$navy_to_process])) {
    $navy = $navies[$navy_to_process];
    $group_id = $navy['group_id'];
    $faction_id = $navy['faction_id'];
    $citizen_id = $navy['citizen_id'];

    // Accesses group members
    $navyFromGroup = getNavyMembers($group_id);
    $navyGroupMembers = array_column($navyFromGroup, 0);

    // Maps rank name to rank id for each userid
    $navyGroupMembersRank = array_map(function ($user) use ($rank_mapping) {
        return [$user[0], $rank_mapping[$user[2]] ?? null];
    }, $navyFromGroup);

    $citizensFromDb = citizenFromDb($conn, $faction_id, $citizen_id);
    $navyFromDb = navyFromDb($conn, $faction_id, $citizen_id);
    $navyDbMembers = array_column($navyFromDb, 0);
    $allFromDb = allFromDb($conn, $faction_id);

    $usersToAdd = !empty($navyGroupMembers)
        ? array_diff($navyGroupMembers, $allFromDb) : [];

    var_dump($usersToAdd);

    $usersToAmmend = !empty($navyGroupMembers)
        ? array_intersect($navyGroupMembers, $citizensFromDb) : [];

    $usersToRemove = !empty($navyDbMembers)
        ? array_diff($navyDbMembers, $navyGroupMembers) : [];

    if (!empty($usersToAdd)) {
        $userArray = getPfp($usersToAdd);
        $groupMembersAssoc = array_column($navyFromGroup, null, 0);

        foreach ($userArray as &$navyMember) {
            $userId = $navyMember[0] ?? null;
            if ($userId && isset($groupMembersAssoc[$userId])) {
                $navyMember = array_merge($navyMember, $groupMembersAssoc[$userId]);
            }
        }

        $stmtAddMember = $conn->prepare("INSERT INTO members (id, username, rank_id, image_link, faction_id) VALUES (?, ?, ?, ?, ?)");
        $stmtAddRank = $conn->prepare("INSERT INTO rank_history (member_id, rank_id, effective_date) VALUES (?, ?, ?)");

        foreach ($userArray as $user) {
            if ($user) {
                $userid = $user[0];
                $pfp = $user[1];
                $username = $user[2];
                $rank_id = $rank_mapping[$user[3]] ?? null;

                $stmtAddMember->bind_param("isisi", $userid, $username, $rank_id, $pfp, $faction_id);
                $stmtAddMember->execute();

                $effectiveDate = date("Y-m-d H:i:s");
                $stmtAddRank->bind_param("iis", $userid, $rank_id, $effectiveDate);
                $stmtAddRank->execute();
            }
        }
    }

    if (!empty($navyGroupMembersRank) && !empty($navyFromDb)) {
        $navyFromDbAssoc = array_column($navyFromDb, 1, 0);
        $usersToUpdate = [];

        foreach ($navyGroupMembersRank as $groupUser) {
            if ($groupUser) {
                $userId = $groupUser[0];
                $groupRankId = $groupUser[1];

                if (isset($navyFromDbAssoc[$userId]) && $groupRankId != $navyFromDbAssoc[$userId]) {
                    $usersToUpdate[] = ['user_id' => $userId, 'new_rank_id' => $groupRankId];
                }
            }
        }
    }

    if (!empty($usersToAmmend)) {
        $usersRankToAmend = array_map(function ($userId) {
            return ['user_id' => $userId, 'new_rank_id' => 2]; // Crewman rank
        }, $usersToAmmend);

        $stmtUpdateMember = $conn->prepare("UPDATE members SET rank_id = ?, join_date = ?, promotion_date = NULL WHERE id = ?");
        $stmtAddRank = $conn->prepare("INSERT INTO rank_history (member_id, rank_id, effective_date) VALUES (?, ?, ?)");

        foreach ($usersRankToAmend as $user) {
            $joinDate = date("Y-m-d H:i:s");
            $stmtUpdateMember->bind_param("isi", $user['new_rank_id'], $joinDate, $user['user_id']);
            $stmtUpdateMember->execute();

            $stmtAddRank->bind_param("iis", $user['user_id'], $user['new_rank_id'], $joinDate);
            $stmtAddRank->execute();
        }
    }

    if (!empty($usersToUpdate)) {
        $stmtUpdateMember = $conn->prepare("UPDATE members SET rank_id = ?, promotion_date = ? WHERE id = ?");
        $stmtAddRank = $conn->prepare("INSERT INTO rank_history (member_id, rank_id, effective_date) VALUES (?, ?, ?)");

        foreach ($usersToUpdate as $user) {
            $promotionDate = date("Y-m-d H:i:s");
            $stmtUpdateMember->bind_param("isi", $user['new_rank_id'], $promotionDate, $user['user_id']);
            $stmtUpdateMember->execute();

            $stmtAddRank->bind_param("iis", $user['user_id'], $user['new_rank_id'], $promotionDate);
            $stmtAddRank->execute();
        }
    }

    if (!empty($usersToRemove)) {
        $stmtRemoveMember = $conn->prepare("UPDATE members SET rank_id = 1, join_date = NULL, promotion_date = NULL WHERE id = ?");
        $stmtAddRank = $conn->prepare("INSERT INTO rank_history (member_id, rank_id, effective_date) VALUES (?, 1, ?)");

        foreach ($usersToRemove as $user) {
            $effectiveDate = date("Y-m-d H:i:s");
            $stmtRemoveMember->bind_param("i", $user);
            $stmtRemoveMember->execute();

            $stmtAddRank->bind_param("is", $user, $effectiveDate);
            $stmtAddRank->execute();
        }
    }
} else {
    error_log('Invalid or missing navy parameter. Use ?navy=nbn or ?navy=wcn');
    exit;
}
