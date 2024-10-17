<?php
// test.php

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
    $cohost = isset($_POST['cohost']) ? htmlspecialchars($_POST['cohost']) : ''; // Capture host

    // Display the captured data
    echo "<h1>Form Submission Results</h1>";
    echo "<p><strong>User ID:</strong> $userid</p>";
    echo "<p><strong>Faction ID:</strong> $factionid</p>";
    echo "<p><strong>Event Type:</strong> $eventType</p>";
    echo "<p><strong>Selected Attendees:</strong> $selectedAttendeesInput</p>";
    echo "<p><strong>Start Time:</strong> $startTime</p>";
    echo "<p><strong>End Time:</strong> $endTime</p>";
    echo "<p><strong>Notes:</strong> $notes</p>";
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>CoHost:</strong> $cohost</p>";
} else {
    echo "<p>No data submitted.</p>";
}
