<?php
require_once 'config/database.php';

// Check payment_reference values for specific events
$sql = "SELECT id, payment_reference FROM events WHERE id IN (3, 4, 5)";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "<h2>Payment References for Events</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Event ID</th><th>Payment Reference</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . ($row['payment_reference'] ? $row['payment_reference'] : 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Check if payment_reference column exists
$sql = "SHOW COLUMNS FROM events LIKE 'payment_reference'";
$result = mysqli_query($conn, $sql);

echo "<h2>Column Information</h2>";
if (mysqli_num_rows($result) > 0) {
    echo "payment_reference column exists in the events table.";
} else {
    echo "payment_reference column does NOT exist in the events table.";
}

mysqli_close($conn);
?> 