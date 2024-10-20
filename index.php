<?php
session_start();

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error']; // Store the error message
    unset($_SESSION['error']); // Clear the error message after storing it
}

$selectedOption = isset($_SESSION['selected_navy']) ? $_SESSION['selected_navy'] : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tradelands Navy Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="index.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

</head>

<body>

    <!-- Background Carousel -->
    <div id="backgroundCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active"></div>
            <div class="carousel-item"></div>
            <div class="carousel-item"></div>
            <div class="carousel-item"></div>
        </div>
    </div>

    <!-- Content Box -->
    <div class="content-container position-absolute top-50 start-50 translate-middle d-flex flex-column align-items-center">
        <h1 class="title text-center">Tradelands Navy Dashboard</h1>
        <div class="cover-page text-center">
            <h3>Select Your Navy</h3>
            <hr>
            <div class="d-flex justify-content-between">
                <div class="selection-button button1" data-option="NBN"></div>
                <div class="selection-button button2" data-option="WCN"></div>
            </div>
            <p>Click the button below to authenticate using your Roblox account.</p>
            <button id="loginButton" class="btn btn-primary btn-lg" onclick="location.href = 'login.php'" disabled>Login with Roblox</button>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let selectedNavy = null;

            // Handle selection of options
            $('.selection-button').click(function() {
                // Deselect all buttons
                $('.selection-button').removeClass('active').css('filter', 'grayscale(100%)');

                // Select the clicked button
                selectedNavy = $(this).data('option'); // Get option from data attribute
                $(this).addClass('active').css('filter', 'grayscale(0%)');
                $('#loginButton').prop('disabled', !selectedNavy); // Enable the login button only if a navy option is selected
            });

            // Handle login button click
            $('#loginButton').click(function() {
                if (selectedNavy) {
                    // Store the selected option in a session variable via AJAX
                    $.post('navy_option.php', {
                            selected_navy: selectedNavy
                        })
                        .done(function(response) {
                            // Handle successful response if needed
                            console.log('Success:', response);
                        })
                        .fail(function(jqXHR, textStatus, errorThrown) {
                            // Handle error
                            console.error('Error:', textStatus, errorThrown);
                            alert('An error occurred while processing your request. Please try again.');
                        });
                }
            });
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>


</html>