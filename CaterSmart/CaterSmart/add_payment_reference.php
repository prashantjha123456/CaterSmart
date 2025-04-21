<?php
require_once 'config/database.php';

// Check if payment_reference column exists
$sql = "SHOW COLUMNS FROM events LIKE 'payment_reference'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE events ADD COLUMN payment_reference VARCHAR(100) DEFAULT NULL AFTER payment_status";
    if (mysqli_query($conn, $sql)) {
        echo "Payment reference column added successfully.";
    } else {
        echo "Error adding payment reference column: " . mysqli_error($conn);
    }
} else {
    echo "Payment reference column already exists.";
}

mysqli_close($conn);
?> 