<?php
function navyFromDb($conn, $faction_id)
{
    $sql = "SELECT m.id, m.rank_id
        FROM members AS m 
        JOIN rank AS r ON m.rank_id = r.id 
        WHERE m.faction_id = ? AND m.rank_id != 1
        ORDER BY r.id Desc";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $faction_id);
    $stmt->execute();
    $all_users_result = $stmt->get_result();

    $attendeesArray = [];

    if ($all_users_result) {
        while ($row = mysqli_fetch_assoc($all_users_result)) {
            $attendeesArray[] = [$row['id'], $row['rank_id']];
        }
        return $attendeesArray;
    } else {
        return false;
    }
}

function allFromDb($conn, $faction_id)
{
    $sql = "SELECT m.id, m.rank_id
        FROM members AS m 
        JOIN rank AS r ON m.rank_id = r.id 
        WHERE m.faction_id = ?
        ORDER BY r.id Desc";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $faction_id);
    $stmt->execute();
    $all_users_result = $stmt->get_result();

    $attendeesArray = [];

    if ($all_users_result) {
        while ($row = mysqli_fetch_assoc($all_users_result)) {
            $attendeesArray[] = $row['id'];
        }
        return $attendeesArray;
    } else {
        return false;
    }
}

function citizenFromDb($conn, $faction_id)
{
    $sql = "SELECT m.id
        FROM members AS m 
        JOIN rank AS r ON m.rank_id = r.id 
        WHERE m.faction_id = ? AND m.rank_id = 1
        ORDER BY r.id Desc";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $faction_id);
    $stmt->execute();
    $all_users_result = $stmt->get_result();

    $attendeesArray = [];

    if ($all_users_result) {
        while ($row = mysqli_fetch_assoc($all_users_result)) {
            $attendeesArray[] = $row['id'];
        }
        return $attendeesArray;
    } else {
        return false;
    }
}
