<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer") {
    header("location: ../index.php");
    exit;
}

// Check if event ID is provided
if (!isset($_POST["event_id"]) || empty($_POST["event_id"])) {
    $_SESSION["error"] = "Invalid event ID.";
    header("location: dashboard.php");
    exit;
}

$event_id = $_POST["event_id"];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Verify that the event belongs to the current user
    $sql = "SELECT id FROM events WHERE id = ? AND customer_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $event_id, $_SESSION["id"]);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 0) {
                throw new Exception("You don't have permission to delete this event.");
            }
        } else {
            throw new Exception("Something went wrong. Please try again later.");
        }
        
        mysqli_stmt_close($stmt);
    }

    // First delete related menu items
    $sql = "DELETE FROM event_menu_items WHERE event_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete menu items.");
        }
        
        mysqli_stmt_close($stmt);
    }

    // Then delete the event
    $sql = "DELETE FROM events WHERE id = ? AND customer_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $event_id, $_SESSION["id"]);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete event.");
        }
        
        mysqli_stmt_close($stmt);
    }

    // If we got here, commit the transaction
    mysqli_commit($conn);
    $_SESSION["success"] = "Event deleted successfully.";

} catch (Exception $e) {
    // If there was an error, roll back the transaction
    mysqli_rollback($conn);
    $_SESSION["error"] = $e->getMessage();
}

mysqli_close($conn);
header("location: dashboard.php");
exit;
?> 