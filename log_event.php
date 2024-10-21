<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Not authenticated. Please retry.";
    header("Location: index");
    exit();
}
$activePage = 'log_event';
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

// HTML Echo to prevent XSS
function safeEcho($string)
{
    echo htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
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

// Testing purposes only
if ($userName === "Cjegames") {
    $rank = "Commander";
}

// Prevent Low Ranks from access.

if ($rank === "Crewman" || $rank == "Able Crewman" || $rank == "Specialist") {
    header("Location: home");
    exit();
}

if ($selected_navy === "NBN") {
    $faction_id  = 1;
} else {
    $faction_id = 2;
}

// Default Times:

date_default_timezone_set('America/New_York');

$defaultEndTime = date('H:i'); // current time in HH:MM format

// Prepared Statements to mitigate SQL Injection

// SQL For Users

$sql = "SELECT m.id, m.username, r.name, m.image_link
        FROM members AS m 
        JOIN rank AS r ON m.rank_id = r.id 
        WHERE m.faction_id = ?
        ORDER BY r.id Desc";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faction_id); // "i" indicates an integer
$stmt->execute();
$all_users_result = $stmt->get_result();


if ($all_users_result) {
    while ($row = mysqli_fetch_assoc($all_users_result)) {
        $attendeesArray[] = [$row['id'], $row['name'], $row['username'], $row['image_link']];
    }
}

// SQL For Co-Host

$sql = "SELECT m.id, m.username, r.name, m.image_link
        FROM members AS m 
        JOIN rank AS r ON m.rank_id = r.id 
        WHERE m.faction_id = ? AND m.rank_id > 8";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faction_id);
$stmt->execute();
$cohost_result = $stmt->get_result();

if ($cohost_result) {
    while ($row = mysqli_fetch_assoc($cohost_result)) {
        $cohostArray[] = [$row['id'], $row['name'], $row['username'], $row['image_link']];
    }
}

// SQL For Event Type

$sql = "SELECT `name`, `id` FROM `event_types`;";

$event_types_result = $conn->query($sql);

$eventTypesArray = [];

if ($event_types_result) {
    while ($row = mysqli_fetch_assoc($event_types_result)) {
        // Append each event type name to the array
        $eventTypesArray[] = [
            'name' => $row['name'],
            'id' => $row['id']
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php safeEcho($selected_navy); ?> Event Log</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


    <link rel="stylesheet" href="log_event.css">
</head>

<body>
    <?php include_once("components/nav.php"); ?>

    <div class="container mt-5">
        <h1 class="text-center display-1">Create Event</h1>
        <hr>
        <form method="POST" action="sendEventToDb.php">

            <!-- Form Inputs for ID and Faction -->
            <input type="hidden" id="userid" name="userid" value=<?php safeEcho($id); ?>>
            <input type="hidden" id="factionid" name="factionid" value=<?php safeEcho($faction_id); ?>>

            <!-- Host Section -->
            <div class="row mb-3">
                <div class="col-md-3 d-flex flex-column align-items-center">
                    <div class="card" id="hostStyleCard" data-name="<?php safeEcho($userName); ?>" style="width: 16rem; height: 22rem;">
                        <img src="<?php echo $user['picture']; ?>" class="card-img-top" alt="Host Image">
                        <div class="card-body text-center">
                            <h5 class="card-title">Host: <?php safeEcho($userName); ?></h5>
                            <p class="card-text"><?php safeEcho($rank); ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-dark btn-lg my-3" data-bs-toggle="modal" data-bs-target="#coHostModal">
                        Select Co-host
                    </button>
                </div>

                <!-- Form Input for Username of Host -->
                <input type="hidden" id="host" name="host" value=<?php safeEcho($id); ?>>

                <!-- Co-host Section -->
                <div class="col-md-3">
                    <div class="d-flex justify-content-end">
                    </div>
                    <div id="coHostCard" class="d-flex flex-column align-items-center" style="display:none;">
                        <!-- Placeholder for co-host card -->
                    </div>
                </div>
            </div>

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
                                    <div class="col-12 col-sm-6 col-lg-3 mb-3" data-username="<?php safeEcho(strtolower($coHost[2])); ?>">
                                        <div class="card" style="cursor:pointer;"
                                            onclick="selectCoHost('<?php safeEcho($coHost[0]); ?>','<?php safeEcho($coHost[2]); ?>','<?php safeEcho($coHost[1]); ?>', '<?php safeEcho($coHost[3]); ?>')">
                                            <img src="<?php safeEcho($coHost[3]); ?>" class="card-img-top" alt="Co-host Image">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php safeEcho($coHost[2]); ?></h5>
                                                <p class="card-text"><?php safeEcho($coHost[1]); ?></p>
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
                <div class="header text-center">
                    <h4 class="section-title mb-3">Select Event Type</h4>
                </div>
                <div class="row mb-2">
                    <?php foreach ($eventTypesArray as $events): ?>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3 event-type-card" data-event-type="<?php safeEcho(strtolower($events['id'])); ?>">
                            <div class="card event-card-inner" style="cursor:pointer;">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?php safeEcho($events['name']); ?></h5>
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
            <div class="row mb-3 text-center">
                <div class="col-md-6">
                    <label for="startTime" class="form-label">Event Start Time</label>
                    <input type="time" class="form-control" id="startTime" name="startTime" required>
                </div>
                <div class="col-md-6">
                    <label for="endTime" class="form-label">Event End Time</label>
                    <input type="time" class="form-control" id="endTime" name="endTime" value="<?php safeEcho($defaultEndTime); ?>" required>
                </div>
                <small class="text-muted mt-1" id="currentTime">All Events are logged in Eastern Time. The current time is <?php safeEcho($defaultEndTime); ?>.</small>
            </div>

            <!-- Attendees Modal -->
            <div class="modal fade" id="attendeesModal" tabindex="-1" aria-labelledby="attendeesModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" style="width: 80%; height: 80%;">
                    <div class="modal-content" style="height: 100%;">
                        <div class="modal-header">
                            <h5 class="modal-title" id="attendeesModalLabel">Select Attendees</h5>
                            <!-- Search Bar -->
                            <div class="search-container my-3" style="width: 300px" ;>
                                <input type="text" id="attendeeSearch" class="form-control" placeholder="Search for a username...">
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="height: 90%; overflow-y: auto;">
                            <!-- Attendees Cards -->
                            <div class="row" id="attendeeCards">
                                <?php foreach ($attendeesArray as $attendee): ?>
                                    <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-2 attendee-card" data-username="<?php safeEcho(strtolower($attendee[2])); ?>">
                                        <div class="card attendee-card-inner" style="cursor:pointer;"
                                            onclick="selectAttendee('<?php safeEcho($attendee[0]); ?>', '<?php safeEcho($attendee[2]); ?>', '<?php safeEcho($attendee[1]); ?>', '<?php safeEcho($attendee[3]); ?>')">
                                            <img src="<?php safeEcho($attendee[3]); ?>" class="card-img-top" alt="Attendee Image">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php safeEcho($attendee[2]); ?></h5>
                                                <p class="card-text"><?php safeEcho($attendee[1]); ?></p>
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
            <div class="text-center mt-3" id="selectedAttendeesContainer" class="mt-3" style="display:none;">
                <h4>Selected Attendees:</h4>
                <div class="row justify-content-center" id="selectedAttendees">
                    <!-- Placeholder for selected attendee cards -->
                </div>
                <small class="text-muted mt-1">Click on the attendee card to remove them.</small>
            </div>


            <!-- Select Attendees Button -->
            <div class="my-3 text-center">
                <button type="button" class="btn btn-dark btn-lg" data-bs-toggle="modal" data-bs-target="#attendeesModal">
                    Select Attendees
                </button>
            </div>

            <!-- Hidden input for selected attendees -->
            <input type="hidden" id="selectedAttendeesInput" name="selectedAttendeesInput" value="">


            <!-- Notes Section -->
            <div class="mb-3">
                <label for="notes" class="form-label lead">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter any notes here..." required></textarea>
            </div>

            <hr>

            <!-- Submit Button -->
            <div class="text-center">
                <p class="lead">
                    Please ensure all details are correct before submitting.
                </p>
                <button type="button" id="submitEventForm" class="btn btn-dark btn-lg mb-5">Submit</button>
            </div>

        </form>
    </div>
    <?php include_once("components/footer.html"); ?>
</body>
<script>
    var selectedAttendees = [];

    function updateTime() {
        // Get the current time in Eastern Time (EST/EDT)
        const options = {
            timeZone: 'America/New_York',
            hour12: false
        };
        const now = new Date().toLocaleString('en-US', options);

        const dateObj = new Date(now);

        // Extract hours and minutes
        const hours = String(dateObj.getHours()).padStart(2, '0');
        const minutes = String(dateObj.getMinutes()).padStart(2, '0');

        // Update the content of the time display
        document.getElementById('currentTime').innerHTML = `All Events are logged in Eastern Time. The current time is ${hours}:${minutes}.`;
    }
    // Checks current time every 10 seconds.
    setInterval(updateTime, 10000);

    // Search functionality for attendees
    $('#attendeeSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#attendeeCards .attendee-card').filter(function() {
            $(this).toggle($(this).data('username').indexOf(value) > -1);
        });
    });

    // Background for Host & Co-Host to show they are not able to be selected.
    function applyHostCohostStyles() {
        const host = document.getElementById('hostStyleCard').getAttribute('data-name');
        const cohost = document.getElementById('cohostStyleCard')
        let cohostVal = "";

        if (cohost) {
            cohostVal = cohost.getAttribute('data-name');
        }

        // Apply grey background to host card
        $(`.attendee-card-inner:contains('${host}')`).css({
            'background-color': 'lightgrey',
            'cursor': 'default'
        });

        // Apply grey background to co-host card
        if (cohostVal) {
            $(`.attendee-card-inner:contains('${cohostVal}')`).css({
                'background-color': 'lightgrey',
                'cursor': 'default'
            });
        }
    }

    $('#attendeesModal').on('show.bs.modal', function() {
        applyHostCohostStyles(); // Ensure host and co-host cards have grey background
    });

    function removeAttendee(attendee) {
        const index = selectedAttendees.findIndex(att => att.username === attendee);
        if (index !== -1) {
            selectedAttendees.splice(index, 1);
            $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').hide();
            updateSelectedAttendeesInput();

            // Call the click handler directly
            attendeeCardUpdateHandler.call($('.doneSelectingAttendees')[0]);
        }
    }

    function selectCoHost(id, coHost, rank, imgSrc) {
        const index = selectedAttendees.findIndex(att => att.username === coHost);
        if (index !== -1) {
            removeAttendee(coHost);
        }
        // Display the co-host card when selected
        document.getElementById('coHostCard').style.display = 'block';
        document.getElementById('coHostCard').innerHTML = `
            <div class="card" id="cohostStyleCard" data-name="${coHost}" style="width: 16rem; height: 22rem;" onclick="removeCoHost('${coHost}')">
                <img src="${imgSrc}" class="card-img-top" alt="Co-host Image">
                <div class="card-body text-center">
                    <h5 class="card-title">Co-host: ${coHost}</h5>
                    <p class="card-text">${rank}</p>
                </div>
            </div>
            <small class="text-muted my-4">Click the card to remove Co-Host.</small>
            <input type="hidden" id="cohost" name="cohost" value=${id}>`;
        $('#coHostModal').modal('hide'); // Close modal after selection

    }

    function removeCoHost(coHost) {
        // Hide the co-host card
        document.getElementById('coHostCard').style.display = 'none';
        document.getElementById('coHostCard').innerHTML = '';
        $(`.attendee-card-inner:contains('${coHost}')`).css({
            'background-color': '',
            'cursor': 'pointer'
        });
    }

    // Function to handle attendee selection
    function selectAttendee(id, attendee, rank, imgSrc) {
        const index = selectedAttendees.findIndex(att => att.username === attendee);
        const host = document.getElementById('hostStyleCard').getAttribute('data-name');
        const cohost = document.getElementById('cohostStyleCard')
        let cohostVal = "";
        if (cohost) {
            cohostVal = cohost.getAttribute('data-name');
        }

        if (index === -1 && attendee !== host && attendee !== cohostVal) {
            // Add attendee if not already selected
            selectedAttendees.push({
                id: id,
                username: attendee,
                rank: rank,
                image: imgSrc
            });
            $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').show();
        } else {
            // Remove attendee if already selected
            if (index !== -1) {
                selectedAttendees.splice(index, 1);
                $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').hide();
            }
        }

        // Update hidden input with selected attendees
        updateSelectedAttendeesInput();
    }

    // Function to update the hidden input with selected attendees
    function updateSelectedAttendeesInput() {
        // Extract usernames from the selected attendees array
        const attendeeIds = selectedAttendees.map(att => att.id);

        document.getElementById('selectedAttendeesInput').value = JSON.stringify(attendeeIds);
    }

    // Event Handler to update selected attendees
    const attendeeCardUpdateHandler = function() {
        $('#attendeesModal').modal('hide');
        const attendeesContainer = document.getElementById('selectedAttendees');
        attendeesContainer.innerHTML = ''; // Clear current attendees
        updateSelectedAttendeesInput();

        if (selectedAttendees.length > 0) {
            document.getElementById('selectedAttendeesContainer').style.display = 'block';

            selectedAttendees.forEach(attendee => {
                attendeesContainer.innerHTML += `
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="card selected-attendee-card mb-3" onclick="removeAttendee('${attendee.username}')">
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
    }

    // Event listener to trigger function that updates selected attendees on modal close
    $('#doneSelectingAttendees').on('click', attendeeCardUpdateHandler);

    function convertToMinutes(time) {
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    }


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
        let proceed = true;

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

        // Time Logic
        const startMinutes = convertToMinutes(startTime);
        let endMinutes = convertToMinutes(endTime);

        if (endMinutes < startMinutes) {
            const userConfirmed = confirm("Possible invalid time range. End time is before start time on the next day. Please confirm this is correct.");
            if (!userConfirmed) {
                return; // Exit if the user does not confirm
            }
        }

        // TL Logic Validation
        const hrAllowed = ["Captain", "Commodore", "Vice Admiral", "Admiral", "Grand Sea Lord", "King"];
        if ((selectedEventType === "evaluation" || selectedEventType === "war battle") && !hrAllowed.includes("<?php echo $rank; ?>")) {
            alert("<?php echo $rank; ?> are not permitted to submit this event type.")
            return;
        }

        // If validation passes, submit the form
        document.querySelector('form').submit();

        alert("Event form submitted successfully!");
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</html>