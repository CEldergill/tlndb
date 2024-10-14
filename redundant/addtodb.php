<?php
// Database connection
require 'includes/db.php';

// Path to the uploaded CSV file
$file = 'July NBN Copy - Main.csv';

// Rank titles and their corresponding IDs
$rank_mapping = [
    'Citizen' => 1,
    'Crewman' => 2,
    'Able Crewman' => 3,
    'Specialist' => 4,
    'Midshipman' => 5,
    'Lieutenant' => 6,
    'Commander' => 7,
    'Captain' => 8,
    'Commodore' => 9,
    'Vice Admiral' => 10,
    'Admiral' => 11,
    'Grand Sea Lord' => 12,
    'King' => 13
];

// Open the CSV file for reading
if (($handle = fopen($file, "r")) !== FALSE) {
    // Skip the first five rows to get to row 6 (index 5)
    for ($i = 0; $i < 9; $i++) {
        fgetcsv($handle); // Skip rows
    }

    // Initialize row index
    $row_index = 10; // Start from row 6 (actual data starts here)

    // Loop through each row
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $username = $data[1]; // Column A
        $id = $data[2]; // Column B
        $rank_title = $data[3]; // Column C
        $join_date = $data[6]; // Column E
        $faction_id = 1; // Constant value

        // Debugging: Print the entire row of data with row index
        echo "Processing row $row_index: " . implode(", ", $data) . "\n";

        // Trim whitespace from rank_title
        $rank_title = trim($rank_title);

        // Get the rank_id based on the rank_title
        $rank_id = isset($rank_mapping[$rank_title]) ? $rank_mapping[$rank_title] : null;

        // Debugging: Print the rank title and corresponding rank ID
        if ($rank_id === null) {
            echo "Warning: Rank title '$rank_title' not found in mapping.\n";
        }

        // Logic to find the promotion date
        $promotion_date = NULL;
        if ($rank_id !== null) {
            // Get the promotion date from the appropriate column (starting from row 6)
            $promotion_date = $data[6 + (($rank_id - 2) * 2)]; // Adjust for zero-indexing (rank 2 starts at column 6)

            // Check if promotion date is valid, format it, or leave it as NULL
            if (!empty($promotion_date)) {
                $promotion_date = date('Y-m-d H:i:s', strtotime($promotion_date));
            }
        }

        // Format join_date to include time (set to midnight)
        $join_date_formatted = date('Y-m-d H:i:s', strtotime($join_date));

        // Check if the member exists in the database (to update or insert)
        $sql = "SELECT id FROM members WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update existing member
            $update_sql = "UPDATE members SET username = ?, rank_id = ?, join_date = ?, promotion_date = ?, faction_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssii", $username, $rank_id, $join_date_formatted, $promotion_date, $faction_id, $id);
            $update_stmt->execute();
        } else {
            // Insert new member
            $insert_sql = "INSERT INTO members (id, username, rank_id, join_date, promotion_date, faction_id) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("issssi", $id, $username, $rank_id, $join_date_formatted, $promotion_date, $faction_id);
            $insert_stmt->execute();
        }

        $stmt->close();
        $row_index++; // Increment row index
    }

    fclose($handle);
}

// Close the database connection
$conn->close();
