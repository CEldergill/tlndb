<?php
#data
$token = $_SESSION['access']['token'];

$user = $_SESSION['user'];
$rank = $user['rank'];

// Testing purposes only
if ($user['preferred_username'] === "Cjegames") {
    $rank = "Commander";
}

$selected_navy = $user['selected_navy'];
$id = $user['sub'];

// Navy Text

if ($selected_navy === "NBN") {
    $navy = "Nova Balreska";
    $navy_img = "assets/nbn.png";
} else {
    $navy = "Whitecrest";
    $navy_img = "assets/wcn.png";
}

// Get profile picture
$pfp_link = $user['picture'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        .profile-img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }

        .nav-link.disabled {
            color: #adb5bd;
            pointer-events: none;
            opacity: 0.65;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>

<nav class="d-flex flex-wrap justify-content-between align-items-center py-3 px-3 bg-dark text-light">
    <!-- Logos -->
    <div class="d-flex align-items-center me-auto">
        <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-5 text-decoration-none">
            <img src="assets/logo-white.png" alt="Tradelands" width="204" height="60">
        </a>
        <img src="<?php echo $navy_img ?>" alt="Navy" width="100" height="60">
    </div>

    <!-- Select Navy for moderators -->
    <?php if ($id === 89370200) { ?>
        <select class="form-select" aria-label="Select Navy">
            <option selected>Select Navy</option>
            <option value="1">Nova Balreska</option>
            <option value="2">Whitecrest Navy</option>
        </select>
    <?php } ?>
    <input type="hidden" id="selectedNavy" name="selectedNavy" value="">

    <!-- Navigation Options -->
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a href="home" class="nav-link <?php echo ($activePage == 'home') ? 'active' : ''; ?>">Home</a>
        </li>
        <li class="nav-item dropdown">
            <button class="nav-link limited dropdown-toggle <?php echo ($activePage == 'log_event') ? 'active' : ''; ?>" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Event Logs
            </button>
            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item limited <?php echo ($activePage == 'log_event') ? 'active' : ''; ?>" href="log_event">Submit Event Log</a></li>
                <li><a class="dropdown-item limited <?php echo ($activePage == 'view_events') ? 'active' : ''; ?>" href="view_events">View Event Logs</a></li>
            </ul>
        </li>
        <li class="nav-item"><a href="#" class="nav-link limited disabled">War Eligibility</a></li>
        <li class="nav-item"><a href="#" class="nav-link limited disabled">HR Dashboard</a></li>
        <li class="nav-item"><a href="#" class="nav-link limited disabled">Quota</a></li>
        <li class="nav-item"><a href="#" class="nav-link limited disabled">Medals</a></li>
        <li class="nav-item"><a href="logout" class="nav-link btn btn-danger">Logout</a></li>
    </ul>

    <!-- Profile Picture -->
    <div class="profile-picture ms-3">
        <img src="<?php echo $pfp_link ?>" alt="Profile" width="48" height="48" class="rounded-circle">
    </div>
</nav>

<script>
    $(document).ready(function() {

        //Prevent LR's from accessing HR Documents
        var rank = "<?php echo $rank
                    ?>";
        if (rank === "Crewman" || rank === "Able Crewman" || rank === "Specialist") {
            $('.limited').addClass('disabled');
        }

        // Handle active state switching
        $('.nav-link').on('click', function(e) {
            // Check if the clicked link is disabled
            if ($(this).hasClass('disabled')) {
                e.preventDefault();
                return; // Exit the function
            }

            // Remove active class from all links and add it to the clicked link if it's not a dropdown button
            if (!$(this).hasClass('dropdown-toggle')) {
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
            }
        });

        // Add click event for dropdown items
        $('.dropdown-item').on('click', function() {
            // Remove active class from all links
            $('.nav-link').removeClass('active');

            // Add active class to the clicked dropdown item
            $(this).addClass('active');

            // Also, add active class to the parent dropdown toggle
            $(this).closest('.dropdown').find('.dropdown-toggle').addClass('active');
        });

        $('#navySelect').change(function() {
            var selectedValue = $(this).val(); // Get the selected value

            // Check if a valid option is selected
            if (selectedValue) {
                $.ajax({
                    type: 'POST',
                    url: 'update_navy_option.php', // The PHP script to handle the request
                    data: {
                        selectedNavy: selectedValue
                    }, // Data to send to the server
                    success: function(response) {
                        // Handle the response from the PHP script
                        console.log('Response from server:', response);
                    },
                    error: function(xhr, status, error) {
                        // Handle errors here
                        console.error('AJAX Error:', status, error);
                    }
                });
            }
        });
    });
</script>