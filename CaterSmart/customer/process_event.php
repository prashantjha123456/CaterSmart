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
    // Validate input
    $event_type = trim($_POST["event_type"]);
    $event_date = trim($_POST["event_date"]);
    $guest_count = trim($_POST["guest_count"]);
    $custom_requests = trim($_POST["custom_requests"]);
    $menu_items = isset($_POST["menu_items"]) ? $_POST["menu_items"] : [];

    // Validate required fields
    if (empty($event_type) || empty($event_date) || empty($guest_count) || empty($menu_items)) {
        $_SESSION["error"] = "Please fill all required fields and select at least one menu item.";
        header("location: create_event.php");
        exit;
    }

    // Validate guest count
    if (!is_numeric($guest_count) || $guest_count < 1) {
        $_SESSION["error"] = "Please enter a valid number of guests.";
        header("location: create_event.php");
        exit;
    }

    // Validate date
    $current_date = date("Y-m-d");
    if ($event_date < $current_date) {
        $_SESSION["error"] = "Event date cannot be in the past.";
        header("location: create_event.php");
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert event into database
        $sql = "INSERT INTO events (customer_id, event_type, event_date, guest_count, custom_requests, payment_status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "issis", 
                $_SESSION["id"], 
                $event_type, 
                $event_date, 
                $guest_count, 
                $custom_requests
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $event_id = mysqli_insert_id($conn);
                
                // Insert selected menu items
                $sql = "INSERT INTO event_menu_items (event_id, menu_item_id, quantity) VALUES (?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    foreach ($menu_items as $menu_item_id) {
                        mysqli_stmt_bind_param($stmt, "iii", $event_id, $menu_item_id, $guest_count);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Error adding menu items");
                        }
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    throw new Exception("Error preparing menu items statement");
                }

                mysqli_commit($conn);
                $_SESSION["success"] = "Event created successfully! Please complete the payment to confirm your booking.";
                header("location: view_event.php?id=" . $event_id);
                exit;
            } else {
                throw new Exception("Error creating event");
            }

            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Error preparing statement");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION["error"] = "Something went wrong. Please try again later.";
        header("location: create_event.php");
        exit;
    }
} else {
    // If not POST request, redirect to create event page
    header("location: create_event.php");
    exit;
}
?> 