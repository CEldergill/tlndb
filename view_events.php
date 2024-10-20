<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Not authenticated. Please retry.";
    header("Location: index.php");
    exit();
}
$activePage = 'view_events';
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
    header("Location: home.php");
    exit();
}

if ($selected_navy === "NBN") {
    $faction_id  = 1;
} else {
    $faction_id = 2;
}

// Get Users for Attendees Modal

$sql = "SELECT m.username, r.name, m.image_link
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
        $attendeesArray[] = [$row['name'], $row['username'], $row['image_link']];
    }
}

// GET EVENT LOG SQL

$sql = "SELECT \n"

    . "    e.id,\n"

    . "    e.event_date AS event_date,\n"

    . "    e.start_time AS start_time,\n"

    . "    e.end_time AS end_time,\n"

    . "    host.username AS host_name,\n"

    . "    co_host.username AS co_host_name,\n"

    . "    et.name AS event_type,\n"

    . "    GROUP_CONCAT(members.username ORDER BY members.username ASC) AS attendees,\n"

    . "    GROUP_CONCAT(members.image_link ORDER BY members.username ASC) AS attendee_images,\n"

    . "    GROUP_CONCAT(rank.name ORDER BY members.username ASC) AS attendee_ranks,\n"

    . "    e.notes\n"

    . "FROM \n"

    . "    event_log e\n"

    . "LEFT JOIN members host ON e.host_id = host.id\n"

    . "LEFT JOIN members co_host ON e.co_host_id = co_host.id\n"

    . "LEFT JOIN event_attendees ea ON e.id = ea.event_id\n"

    . "LEFT JOIN members ON ea.member_id = members.id\n"

    . "LEFT JOIN rank ON members.rank_id = rank.id\n"

    . "LEFT JOIN event_types et ON e.event_type_id = et.id\n"

    . "WHERE \n"

    . "    e.faction_id = ?\n"

    . "GROUP BY \n"

    . "    e.id, host.username, co_host.username, et.name, e.event_date, e.start_time, e.end_time, e.notes;";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faction_id);
$stmt->execute();
$events_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php safeEcho($selected_navy); ?> Events</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


    <link rel="stylesheet" href="view_events.css">
</head>

<body>

    <?php include_once("components/nav.php"); ?>

    <div class="container mt-5">
        <h1 class="text-center display-1">View Events</h1>
        <hr>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Host</th>
                        <th>Co-Host</th>
                        <th>Event Type</th>
                        <th>Attendees</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $events_result->fetch_assoc()) {
                        // Prepare data for modal
                        $attendee_names = explode(",", $row['attendees']);
                        $attendee_images = explode(",", $row['attendee_images']);
                        $attendee_ranks = explode(",", $row['attendee_ranks']);

                        // Combine them into a JSON-like structure for easier handling in JavaScript
                        $attendees = [];
                        for ($i = 0; $i < count($attendee_names); $i++) {
                            $attendees[] = [
                                "name" => htmlspecialchars($attendee_names[$i]),
                                "image" => htmlspecialchars($attendee_images[$i]),
                                "rank" => htmlspecialchars($attendee_ranks[$i])
                            ];
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo date("F j, Y", strtotime($row['event_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['host_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['co_host_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['event_type']); ?></td>
                            <td>
                                <span class="show-modal"
                                    data-content='<?php echo json_encode($attendees); ?>'
                                    data-type="attendees"
                                    style="cursor: pointer; color: blue; text-decoration: underline;">
                                    View Attendees
                                </span>
                            </td>
                            <td><?php echo date("g:i A", strtotime($row['start_time'])); ?></td>
                            <td><?php echo date("g:i A", strtotime($row['end_time'])); ?></td>
                            <td>
                                <span class="show-modal"
                                    data-content="<?php echo htmlspecialchars($row['notes']); ?>"
                                    data-type="notes"
                                    style="cursor: pointer; color: blue; text-decoration: underline;">
                                    View Notes
                                </span>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>

            </table>
        </div>
    </div>

    <!-- Modal Structure -->
    <div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="contentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contentModalLabel">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Content will be injected here -->
                    <p id="modalContent"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once("components/footer.html"); ?>

</body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        function showModal(content, type) {
            const modalContent = document.getElementById("modalContent");
            modalContent.innerHTML = ''; // Clear previous content

            if (type === "attendees") {
                const attendees = JSON.parse(content);

                const row = document.createElement('div');
                row.className = 'row';

                attendees.forEach(function(attendee) {
                    const column = document.createElement('div');
                    column.className = 'col-6 col-sm-4 col-md-3 col-lg-2 mb-2';
                    const attendeeCard = `
                        <div class="selected-attendee-card card">
                            <img src="${attendee.image}" class="card-img-top" alt="${attendee.name}">
                            <div class="card-body">
                                <h5 class="card-title">${attendee.name}</h5>
                                <p class="card-text">${attendee.rank}</p>
                            </div>
                        </div>`;
                    column.innerHTML = attendeeCard; // Insert card HTML into the column
                    row.appendChild(column); // Append column to row
                });

                modalContent.appendChild(row); // Append row to modal content
            } else {
                modalContent.textContent = content; // For notes
            }

            var modal = new bootstrap.Modal(document.getElementById("contentModal"), {});
            modal.show();
        }

        // Event listeners for showing the modal
        document.querySelectorAll(".show-modal").forEach(function(element) {
            element.addEventListener("click", function() {
                const content = this.getAttribute("data-content");
                const type = this.getAttribute("data-type");
                showModal(content, type);
            });
        });
    });
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</html>