<?php
require_once 'config/database.php';

// Read and execute the SQL file
$sql = file_get_contents('sql/init_settings.sql');

if (mysqli_multi_query($conn, $sql)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    
    echo "Settings table initialized successfully.";
} else {
    echo "Error initializing settings table: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 