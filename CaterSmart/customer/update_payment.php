<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer") {
    header("location: ../index.php");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate event ID
    if (!isset($_POST["event_id"]) || empty($_POST["event_id"])) {
        $_SESSION["error"] = "Invalid event ID.";
        header("location: dashboard.php");
        exit;
    }

    $event_id = $_POST["event_id"];

    // Verify event belongs to this customer
    $sql = "SELECT * FROM events WHERE id = ? AND customer_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $event_id, $_SESSION["id"]);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (!mysqli_fetch_assoc($result)) {
                $_SESSION["error"] = "Event not found or you don't have permission to update it.";
                header("location: dashboard.php");
                exit;
            }
        } else {
            $_SESSION["error"] = "Something went wrong. Please try again later.";
            header("location: dashboard.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    }

    // Validate payment method
    if (!isset($_POST["payment_method"]) || empty($_POST["payment_method"])) {
        $_SESSION["error"] = "Please select a payment method.";
        header("location: view_event.php?id=" . $event_id);
        exit;
    }

    $payment_method = $_POST["payment_method"];

    // Update payment details
    $sql = "UPDATE events SET payment_method = ?, payment_status = 'completed', updated_at = NOW() WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $payment_method, $event_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION["success"] = "Payment details updated successfully.";
        } else {
            $_SESSION["error"] = "Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION["error"] = "Something went wrong. Please try again later.";
    }

    header("location: view_event.php?id=" . $event_id);
    exit;
} else {
    header("location: dashboard.php");
    exit;
}
?> 