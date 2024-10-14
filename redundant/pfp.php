<?php
session_start();
require '../includes/db.php';
#data
$token = $_SESSION['access']['token'];
$user = $_SESSION['user'];
$selected_navy = $user['selected_navy'];
$id = $user['sub'];
$displayName = $user['name'];
$userName = $user['preferred_username'];
$rank = $user['rank'];

if ($selected_navy === "NBN") {
    $faction_id  = 1;
} else {
    $faction_id = 2;
}

// SQL For Users

$sql = "SELECT m.id
        FROM members AS m
        WHERE m.image_link = ''";

$all_users_result = $conn->query($sql);

$membersArray = [];

if ($all_users_result) {
    while ($row = mysqli_fetch_assoc($all_users_result)) {
        $membersArray[] = $row['id']; // Add the 'id' from each row to the array
    }
} else {
    echo "Error: " . mysqli_error($conn); // Error handling for SQL query
}

foreach ($membersArray as $id) {
    $id = strval($id);
    echo $id . "\n";
    $pfp_url = "https://thumbnails.roblox.com/v1/users/avatar-headshot?userIds=$id&size=100x100&format=Png&isCircular=true";

    // Initialize cURL for each iteration
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pfp_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

    $pfp_response = curl_exec($ch);

    if ($pfp_response === false) {
        $_SESSION['error'] = 'Curl error: ' . curl_error($ch);
        header("Location: ../index.php");
        exit();
    }

    // Separate headers and body
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($pfp_response, 0, $header_size);
    $body = substr($pfp_response, $header_size);


    // Process the body as JSON
    $pfp_data = json_decode($body, true);

    if ($pfp_data === null) {
        $_SESSION['error'] = 'Error decoding JSON: ' . json_last_error_msg();
        header("Location: ../index.php");
        exit();
    }

    // Verify that data exists in the response
    if (!empty($pfp_data['data'][0]['imageUrl'])) {
        $pfp_link = $pfp_data['data'][0]['imageUrl'];

        // Update the image link in the database
        $update_sql = "UPDATE members SET image_link = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $pfp_link, $id);

        if (!$stmt->execute()) {
            $_SESSION['error'] = 'Database update error: ' . $stmt->error;
            header("Location: ../index.php");
            exit();
        }

        $stmt->close(); // Close the prepared statement
    } else {
        echo "No imageUrl found for user ID: $id\n";
    }

    // Close the cURL session
    curl_close($ch);
}

// Close the database connection
mysqli_close($conn);

// Redirect to a success page or display a success message
exit();
