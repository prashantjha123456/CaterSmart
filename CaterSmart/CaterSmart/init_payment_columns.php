<?php
require_once 'config/database.php';

// Read and execute the SQL file
$sql = file_get_contents('sql/init_payment_columns.sql');

if (mysqli_multi_query($conn, $sql)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    
    echo "Payment columns added successfully.";
} else {
    echo "Error adding payment columns: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 