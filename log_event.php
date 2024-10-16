<?php
session_start();
$activePage = 'log_event';
require 'includes/token_manager.php';
require 'includes/db.php';

$client_secret = getenv('CLIENT_SECRET');
$client_id = getenv('CLIENT_ID');

if (isset($_SESSION['access']['expiry']) && time() >= ($_SESSION['access']['expiry'] - 300)) {
    if (!refreshAccessToken($client_id, $client_secret)) {
        $_SESSION['error'] = "Error: Unable to refresh access token.";
        header("Location: index.php");
        exit();
    }
}

// Array Declaration

$attendeesArray = [];
$cohostArray = [];
$eventTypesArray = [];


// Data
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

$sql = "SELECT m.username, r.name, m.image_link
        FROM members AS m 
        JOIN rank AS r ON m.rank_id = r.id 
        WHERE m.faction_id = $faction_id
        ORDER BY r.id Desc";

$all_users_result = $conn->query($sql);

if ($all_users_result) {
    while ($row = mysqli_fetch_assoc($all_users_result)) {
        $attendeesArray[] = [$row['name'], $row['username'], $row['image_link']];
    }
}

// SQL For Co-Host

$sql = "SELECT m.username, r.name, m.image_link
        FROM members AS m 
        JOIN rank AS r ON m.rank_id = r.id 
        WHERE m.faction_id = $faction_id AND m.rank_id > 8";

$cohost_result = $conn->query($sql);

if ($cohost_result) {
    while ($row = mysqli_fetch_assoc($cohost_result)) {
        $cohostArray[] = [$row['name'], $row['username'], $row['image_link']];
    }
}

// SQL For Event Type

$sql = "SELECT `name` FROM `event_types`;";

$event_types_result = $conn->query($sql);

$eventTypesArray = [];

if ($event_types_result) {
    while ($row = mysqli_fetch_assoc($event_types_result)) {
        // Append each event type name to the array
        $eventTypesArray[] = $row['name'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $selected_navy ?> Event Log</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <link rel="stylesheet" href="log_event.css">
</head>

<body>
    <?php include_once("components/nav.php"); ?>

    <div class="container mt-5">
        <h2>Create Event</h2>
        <form method="POST" action="test.php">

            <!-- Form Inputs for ID and Faction -->
            <input type="hidden" id="userid" name="userid" value=<?php echo $id ?>>
            <input type="hidden" id="factionid" name="factionid" value=<?php echo $faction_id ?>>

            <!-- Host Section -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card" style="width: 15rem;">
                        <img src="<?php echo $user['picture']; ?>" class="card-img-top" alt="Host Image">
                        <div class="card-body text-center">
                            <h5 class="card-title">Host: <?php echo $userName; ?></h5>
                            <p class="card-text"><?php echo $rank; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Form Input for Username of Host -->
                <input type="hidden" id="host" name="host" value=<?php echo $userName ?>>

                <!-- Co-host Section -->
                <div class="col-md-3">
                    <div class="d-flex justify-content-end">
                    </div>
                    <div id="coHostCard" class="" style="display:none;">
                        <!-- Placeholder for co-host card -->
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-dark mb-3" data-bs-toggle="modal" data-bs-target="#coHostModal">
                Select Co-host
            </button>

            <!-- Co Host input in selected card -->


            <!-- Co-host Modal -->
            <div class="modal fade" id="coHostModal" tabindex="-1" aria-labelledby="coHostModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" style="width: 80%; height: 80%;">
                    <div class="modal-content" style="height: 100%;">
                        <div class="modal-header">
                            <h5 class="modal-title" id="coHostModalLabel">Select Co-host</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="height: 90%; overflow-y: auto;">
                            <div class="row">
                                <?php foreach ($cohostArray as $coHost): ?>
                                    <div class="col-3 mb-3" data-username="<?php echo strtolower($coHost[1]); ?>">
                                        <div class="card attendee-card-inner" style="cursor:pointer;"
                                            onclick="selectCoHost('<?php echo $coHost[1]; ?>','<?php echo $coHost[0]; ?>', '<?php echo $coHost[2]; ?>')">
                                            <img src="<?php echo $coHost[2]; ?>" class="card-img-top" alt="Co-host Image">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php echo $coHost[1]; ?></h5>
                                                <p class="card-text"><?php echo $coHost[0]; ?></p>
                                                <span class="checkmark" style="display:none;">✔️</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Event Type Section -->
            <section id="eventTypeSection" class="container mt-2">
                <div class="header">
                    <h5 class="section-title">Select Event Type</h5>
                </div>
                <div class="row">
                    <?php foreach ($eventTypesArray as $events): ?>
                        <div class="col-3 mb-3 event-type-card" data-event-type="<?php echo strtolower($events); ?>">
                            <div class="card attendee-card-inner" style="cursor:pointer;">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?php echo $events; ?></h5>
                                    <span class="checkmark" style="display:none;">✔️</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Hidden input for selected event type -->
            <input type="hidden" id="eventType" name="eventType" value="">


            <!-- Start and End Time Section -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="startTime" class="form-label">Event Start Time</label>
                    <input type="time" class="form-control" id="startTime" required>
                </div>
                <div class="col-md-6">
                    <label for="endTime" class="form-label">Event End Time</label>
                    <input type="time" class="form-control" id="endTime" required>
                </div>
            </div>

            <!-- Attendees Modal -->
            <div class="modal fade" id="attendeesModal" tabindex="-1" aria-labelledby="attendeesModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" style="width: 80%; height: 80%;">
                    <div class="modal-content" style="height: 100%;">
                        <div class="modal-header">
                            <h5 class="modal-title" id="attendeesModalLabel">Select Attendees</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="height: 90%; overflow-y: auto;">
                            <!-- Search Bar -->
                            <div class="mb-3">
                                <input type="text" id="attendeeSearch" class="form-control" placeholder="Search for a username...">
                            </div>
                            <!-- Attendees Cards -->
                            <div class="row" id="attendeeCards">
                                <?php foreach ($attendeesArray as $attendee): ?>
                                    <div class="col-1 mb-3 attendee-card" data-username="<?php echo strtolower($attendee[1]); ?>">
                                        <div class="card attendee-card-inner" style="cursor:pointer;"
                                            onclick="selectAttendee('<?php echo $attendee[1]; ?>', '<?php echo $attendee[0]; ?>', '<?php echo $attendee[2]; ?>')">
                                            <img src="<?php echo $attendee[2]; ?>" class="card-img-top" alt="Attendee Image">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php echo $attendee[1]; ?></h5>
                                                <p class="card-text"><?php echo $attendee[0]; ?></p>
                                                <span class="checkmark" style="display:none;">✔️</span> <!-- Checkmark for selected users -->
                                            </div>
                                        </div>
                                    </div>

                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" id="doneSelectingAttendees">Done</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Attendees Display -->
            <div id="selectedAttendeesContainer" class="mt-3" style="display:none;">
                <h4>Selected Attendees:</h4>
                <div class="row" id="selectedAttendees"></div>
            </div>


            <!-- Selected Attendees Button -->
            <div class="my-3">
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#attendeesModal">
                    Select Attendees
                </button>
            </div>

            <!-- Hidden input for selected attendees -->
            <input type="hidden" id="selectedAttendeesInput" name="selectedAttendees" value="">


            <!-- Notes Section -->
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" rows="3" placeholder="Enter any notes here..." required></textarea>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="button" id="submitEventForm" class="btn btn-dark mb-5">Submit</button>
            </div>

        </form>
    </div>

    <script>
        var selectedAttendees = [];

        // Search functionality for attendees
        $('#attendeeSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#attendeeCards .attendee-card').filter(function() {
                $(this).toggle($(this).data('username').indexOf(value) > -1);
            });
        });

        // Done button logic
        $('#doneSelectingAttendees').on('click', function() {
            $('#attendeesModal').modal('hide');
            console.log('Selected Attendees:', selectedAttendees);
            // Handle logic to update selected attendees on the form
        });

        function selectCoHost(coHost, rank, imgSrc) {
            // Display the co-host card when selected
            document.getElementById('coHostCard').style.display = 'block';
            document.getElementById('coHostCard').innerHTML = `
            <div class="card" style="width: 15rem;">
                <img src="${imgSrc}" class="card-img-top" alt="Co-host Image">
                <div class="card-body text-center">
                    <h5 class="card-title">Co-host: ${coHost}</h5>
                    <p class="card-text">${rank}</p>
                </div>
            </div>
            <input type="hidden" id="cohost" name="cohost" value=${coHost}>`;
            $('#coHostModal').modal('hide'); // Close modal after selection
        }

        // Function to handle attendee selection
        function selectAttendee(attendee, rank, imgSrc) {
            const index = selectedAttendees.findIndex(att => att.username === attendee);

            if (index === -1) {
                // Add attendee if not already selected
                selectedAttendees.push({
                    username: attendee,
                    rank: rank,
                    image: imgSrc
                });
                $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').show();
            } else {
                // Remove attendee if already selected
                selectedAttendees.splice(index, 1);
                $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').hide();
            }

            // Update hidden input with selected attendees
            updateSelectedAttendeesInput();
        }

        // Function to update the hidden input with selected attendees
        function updateSelectedAttendeesInput() {
            // Extract usernames from the selected attendees array
            const attendeeUsernames = selectedAttendees.map(att => att.username);

            // Convert to JSON string format (e.g., ["username1", "username2"])
            document.getElementById('selectedAttendeesInput').value = JSON.stringify(attendeeUsernames);
        }

        // Event listener to display selected attendees on modal close
        $('#doneSelectingAttendees').on('click', function() {
            $('#attendeesModal').modal('hide');
            const attendeesContainer = document.getElementById('selectedAttendees');
            attendeesContainer.innerHTML = ''; // Clear current attendees
            updateSelectedAttendeesInput();

            if (selectedAttendees.length > 0) {
                document.getElementById('selectedAttendeesContainer').style.display = 'block';

                selectedAttendees.forEach(attendee => {
                    attendeesContainer.innerHTML += `
            <div class="col-2">
                <div class="card">
                    <img src="` + attendee.image + `" class="card-img-top" alt="${attendee.username}">
                    <div class="card-body text-center">
                        <h5 class="card-title">` + attendee.username + `</h5>
                        <p class="card-text">` + attendee.rank + `</p>
                    </div>
                </div>
            </div>`;
                });
            } else {
                document.getElementById('selectedAttendeesContainer').style.display = 'none';
            }
        });


        // Event Type logic
        document.addEventListener('DOMContentLoaded', function() {
            const eventCards = document.querySelectorAll('.event-type-card');
            const eventTypeInput = document.getElementById('eventType'); // Get the hidden input

            eventCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Deselect any previously selected card
                    eventCards.forEach(c => {
                        c.classList.remove('selected');
                        const checkmark = c.querySelector('.checkmark');
                        checkmark.style.display = 'none';
                    });

                    // Select the clicked card
                    this.classList.add('selected');
                    const checkmark = this.querySelector('.checkmark');
                    checkmark.style.display = 'inline-block';

                    // Set the hidden input value to the selected event type
                    const selectedEventType = this.getAttribute('data-event-type');
                    eventTypeInput.value = selectedEventType;
                });
            });
        });

        // Validation Logic
        document.getElementById('submitEventForm').addEventListener('click', function() {
            const selectedEventType = document.getElementById('eventType').value;
            const selectedAttendeesInput = document.getElementById('selectedAttendeesInput').value;
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            const notes = document.getElementById('notes').value;

            // Check if an event type is selected
            if (!selectedEventType) {
                alert("Please select an event type.");
                return;
            }

            // Check if there are selected attendees and parse them if they exist
            let selectedAttendees = [];
            if (selectedAttendeesInput) {
                try {
                    selectedAttendees = JSON.parse(selectedAttendeesInput);
                } catch (e) {
                    console.error("Error parsing selected attendees:", e);
                    alert("There was an error with the selected attendees. Please try again.");
                    return;
                }
            }

            if (!startTime) {
                alert("Please enter a start time for the event.");
                return;
            }

            if (!endTime) {
                alert("Please enter an end time for the event.");
                return;
            }

            if (!selectedAttendees || selectedAttendees.length < 3) {
                alert("Please select at least 3 attendees.");
                return;
            }

            if (!notes) {
                alert("Please enter notes for the event.");
                return;
            }

            // If validation passes, submit the form
            document.querySelector('form').submit();

            // Reset the form after successful submission
            alert("Event form submitted successfully!");
            //window.location.href = 'log_event.php'
            //resetForm();
        });
    </script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>