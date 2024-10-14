<?php
session_start();
$activePage = 'log_event';
require 'includes/token_manager.php';
require 'includes/db.php';

if (isset($_SESSION['access']['expiry']) && time() >= ($_SESSION['access']['expiry'] - 300)) {
    if (!refreshAccessToken()) {
        $_SESSION['error'] = "Error: Unable to refresh access token.";
        header("Location: index.php");
        exit();
    }
}

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
        <form>
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

            <!-- Attendees Section -->
            <div class="mb-3">
                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#attendeesModal">
                    Select Attendees
                </button>
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
                                            onclick="selectAttendee('<?php echo $attendee[1]; ?>', '<?php echo $attendee[2]; ?>')">
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

            <!-- Notes Section -->
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" rows="3" placeholder="Enter any notes here..."></textarea>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" class="btn btn-dark mb-5">Submit</button>
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

        // Multi-select attendees
        function selectAttendee(attendee) {
            const index = selectedAttendees.indexOf(attendee);
            if (index === -1) {
                // If the attendee is not already selected, add to the list
                selectedAttendees.push(attendee);
                // Mark the attendee as selected (show checkmark)
                $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').show();
            } else {
                // If the attendee is already selected, remove from the list
                selectedAttendees.splice(index, 1);
                // Unmark the attendee (hide checkmark)
                $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').hide();
            }
        }

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
        </div>`;
            $('#coHostModal').modal('hide'); // Close modal after selection
        }


        // Event Type logic
        document.addEventListener('DOMContentLoaded', function() {
            const eventCards = document.querySelectorAll('.event-type-card');

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
                });
            });
        });



        // Selected attendees
        var selectedAttendees = [];

        function selectAttendee(attendee, imgSrc) {
            const index = selectedAttendees.findIndex(att => att.username === attendee);
            if (index === -1) {
                selectedAttendees.push({
                    username: attendee,
                    image: imgSrc
                });
                $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').show();
            } else {
                selectedAttendees.splice(index, 1);
                $(`.attendee-card-inner:contains('${attendee}')`).find('.checkmark').hide();
            }
        }


        // Handle "Done" button and display selected attendees
        $('#doneSelectingAttendees').on('click', function() {
            $('#attendeesModal').modal('hide');
            const attendeesContainer = document.getElementById('selectedAttendees');
            attendeesContainer.innerHTML = ''; // Clear current attendees

            if (selectedAttendees.length > 0) {
                document.getElementById('selectedAttendeesContainer').style.display = 'block';

                selectedAttendees.forEach(attendee => {
                    attendeesContainer.innerHTML += `
    <div class="col-2">
        <div class="card">
            <img src="` + attendee.image + `" class="card-img-top" alt="${attendee.username}">
            <div class="card-body text-center">
                <h5 class="card-title">` + attendee.username + `</h5>
            </div>
        </div>
    </div>`;
                });
            } else {
                document.getElementById('selectedAttendeesContainer').style.display = 'none';
            }
        });
    </script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>