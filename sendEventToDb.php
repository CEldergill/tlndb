<?php
session_start();
require 'includes/token_manager.php';
require 'includes/db.php';

$client_secret = getenv('CLIENT_SECRET');
$client_id = getenv('CLIENT_ID');

if (isset($_SESSION['access']['expiry']) && time() >= ($_SESSION['access']['expiry'] - 300)) {
    if (!refreshAccessToken($client_id, $client_secret)) {
        $_SESSION['error'] = "Error: Unable to refresh access token.";
        header("Location: index");
        exit();
    }
}


// Check if the form is submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form data
    $userid = isset($_POST['userid']) ? htmlspecialchars($_POST['userid']) : '';
    $factionid = isset($_POST['factionid']) ? htmlspecialchars($_POST['factionid']) : '';
    $eventType = isset($_POST['eventType']) ? htmlspecialchars($_POST['eventType']) : '';
    $selectedAttendeesInput = isset($_POST['selectedAttendeesInput']) ? htmlspecialchars($_POST['selectedAttendeesInput']) : '';
    $startTime = isset($_POST['startTime']) ? htmlspecialchars($_POST['startTime']) : '';
    $endTime = isset($_POST['endTime']) ? htmlspecialchars($_POST['endTime']) : '';
    $notes = isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '';
    $host = isset($_POST['host']) ? htmlspecialchars($_POST['host']) : '';
    $cohost = isset($_POST['cohost']) ? htmlspecialchars($_POST['cohost']) : NULL;

    $startTime = date('H:i:s', strtotime($startTime));
    $endTime = date('H:i:s', strtotime($endTime));

    $sql = "INSERT INTO event_log (faction_id, host_id, co_host_id, start_time, end_time, notes, event_type_id) VALUES (?, ?, ?, ?, ?, ?, ?)";

    // Prepare statement
    $stmt = $conn->prepare($sql);

    // Check if preparation was successful
    if ($stmt) {
        // Bind parameters
        if ($cohost === NULL) {
            $stmt->bind_param("iiisssi", $factionid, $host, $cohost, $startTime, $endTime, $notes, $eventType);
        } else {
            $stmt->bind_param("iiisssi", $factionid, $host, $cohost, $startTime, $endTime, $notes, $eventType);
        }


        // Execute the statement
        if ($stmt->execute()) {
            // Get the last inserted event ID
            $eventId = $stmt->insert_id;

            $selectedAttendees = json_decode(html_entity_decode(stripslashes($selectedAttendeesInput)), true); //Decode String

            foreach ($selectedAttendees as $attendeeId) {
                $attendeeId = trim($attendeeId);
                $attendanceSql = "INSERT INTO event_attendees (event_id, member_id) VALUES (?, ?)";
                $attendanceStmt = $conn->prepare($attendanceSql);

                if ($attendanceStmt) {
                    $attendanceStmt->bind_param("ii", $eventId, $attendeeId);
                    $attendanceStmt->execute();
                    $attendanceStmt->close();
                }
            }

            echo "Event created successfully!";
        } else {
            echo "Error: " . $stmt->error; // Handle execution error
        }

        $stmt->close(); // Close the event log statement
    } else {
        echo "Error preparing statement: " . $conn->error; // Handle preparation error
    }
}

$conn->close(); // Close the database connection
header("Location: home");
