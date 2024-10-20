<?php
session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Not authenticated. Please retry.";
    header("Location: index");
    exit();
}

$client_secret = getenv('CLIENT_SECRET');
$client_id = getenv('CLIENT_ID');

$activePage = 'home';
require 'includes/token_manager.php';
require 'includes/db.php';

// Refresh the access token if it's about to expire
if (isset($_SESSION['access']['expiry']) && time() >= ($_SESSION['access']['expiry'] - 300)) {
    if (!refreshAccessToken($client_id, $client_secret)) {
        $_SESSION['error'] = "Error: Unable to refresh access token.";
        header("Location: index");
        exit();
    }
}

function safeEcho($string)
{
    echo htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Data retrieval
$token = $_SESSION['access']['token'];
$user = $_SESSION['user'];
$selected_navy = $user['selected_navy'];
$id = $user['sub'];
$displayName = $user['name'];
$userName = $user['preferred_username'];
$rank = $user['rank'];

// Determine Navy information
$navies = [
    "NBN" => ["Nova Balreska", "assets/nbn.png"],
    "WCN" => ["Whitecrest", "assets/wcn.png"]
];

if (isset($navies[$selected_navy])) {
    [$navy, $navy_img] = $navies[$selected_navy];
} else {
    $_SESSION['error'] = "No navy authenticated. Please retry.";
    header("Location: index");
    exit();
}

// Calculate Days in Navy
$sql = "SELECT m.join_date FROM members AS m WHERE m.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$date = $result->fetch_assoc();
$user_days_in_navy = $date ? date_diff(new DateTime($date['join_date']), new DateTime())->days : "No Data Found.";

// Card data (easily extendable)
$cards = [
    ["title" => "Days in the Navy", "value" => $user_days_in_navy],
    ["title" => "Events Attended", "value" => "No Data Available in Testing"],
    ["title" => "Events Hosted", "value" => "No Data Available in Testing"],
    ["title" => "Promotion Date", "value" => "No Data Available in Testing"]
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome, <?php safeEcho($displayName); ?>!</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }
    </style>
</head>

<body>
    <?php include_once("components/nav.php"); ?>

    <main>
        <div class="position-relative overflow-hidden p-3 p-md-3 text-center bg-body-tertiary">
            <div class="col-md-6 p-lg-5 mx-auto my-5">
                <h1 class="display-3 fw-bold">Welcome to the <?php safeEcho($navy); ?> Navy Dashboard</h1>
                <h3 class="fw-normal text-muted mb-3"><?php echo $rank . " " . $userName; ?></h3>
            </div>
        </div>

        <div class="container my-5">
            <div class="row text-center">
                <?php foreach ($cards as $card): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card text-dark bg-light rounded shadow p-3 mb-5">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $card['title']; ?></h5>
                                <hr>
                                <h2><?php safeEcho($card['value']); ?></h2>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php include_once("components/footer.html"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>