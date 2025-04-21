<?php
require_once 'config/database.php';

// Check if payment_status column exists
$sql = "SHOW COLUMNS FROM events LIKE 'payment_status'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE events ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending'";
    if (mysqli_query($conn, $sql)) {
        echo "Payment status column added successfully.";
    } else {
        echo "Error adding payment status column: " . mysqli_error($conn);
    }
} else {
    echo "Payment status column already exists.";
}

mysqli_close($conn);
?> 