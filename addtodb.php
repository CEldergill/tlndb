<?php
// Database connection
require 'includes/db.php';

// Path to the uploaded CSV file
$file = 'Copy of WCN Database - September 10, 10_24 AM - Main.csv';

// Rank titles in the order they appear in the CSV (row 2)
$ranks = [
    'Able Crewman',
    'Specialist',
    'Midshipman',
    'Lieutenant',
    'Commander',
    'Captain',
    'Commodore',
    'Vice Admiral',
    'Admiral'
];

// Open the CSV file for reading
if (($handle = fopen($file, "r")) !== FALSE) {
    fgetcsv($handle); // Skip the first row (header)

    // Loop through each row
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $username = $data[0]; // Column A
        $id = $data[1]; // Column B
        $rank = $data[2]; // Column C
        $join_date = $data[4]; // Column E
        $faction_id = 2; // Constant value

        // Logic to find the promotion date
        $promotion_date = NULL;
        $rank_index = array_search($rank, $ranks);

        if ($rank_index !== false) {
            // Get the promotion date from the appropriate column (starting from row 7)
            $row_index = 7; // Row 8 in zero-indexed array
            $promotion_date = $data[6 + ($rank_index * 2)];

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
            $update_sql = "UPDATE members SET username = ?, rank = ?, join_date = ?, promotion_date = ?, faction_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssii", $username, $rank, $join_date_formatted, $promotion_date, $faction_id, $id);
            $update_stmt->execute();
        } else {
            // Insert new member
            $insert_sql = "INSERT INTO members (id, username, rank, join_date, promotion_date, faction_id) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("issssi", $id, $username, $rank, $join_date_formatted, $promotion_date, $faction_id);
            $insert_stmt->execute();
        }

        $stmt->close();
    }

    fclose($handle);
}

// Close the database connection
$conn->close();
