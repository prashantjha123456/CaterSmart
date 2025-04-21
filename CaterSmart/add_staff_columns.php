<?php
require_once 'config/database.php';

// Add staff-related columns to events table
$columns = [
    'staff_count' => 'INT DEFAULT 1',
    'staff_type' => 'VARCHAR(20) DEFAULT "basic"',
    'event_duration' => 'INT DEFAULT 4',
    'staff_cost' => 'DECIMAL(10,2) DEFAULT 0.00'
];

foreach ($columns as $column => $definition) {
    $sql = "SHOW COLUMNS FROM events LIKE '$column'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE events ADD COLUMN $column $definition";
        if (mysqli_query($conn, $sql)) {
            echo "Column $column added successfully.<br>";
        } else {
            echo "Error adding column $column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Column $column already exists.<br>";
    }
}

mysqli_close($conn);
?> 