<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

// Check if ID parameter exists
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    $_SESSION["error"] = "Invalid inventory item ID.";
    header("location: dashboard.php");
    exit;
}

$id = $_GET["id"];

// Prepare a delete statement
$sql = "DELETE FROM inventory WHERE id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION["success"] = "Inventory item deleted successfully.";
    } else {
        $_SESSION["error"] = "Error deleting inventory item.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION["error"] = "Error preparing statement.";
}

// Redirect back to dashboard
header("location: dashboard.php");
exit;
?> 