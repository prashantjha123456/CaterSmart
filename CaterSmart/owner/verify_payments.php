<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

$message = "";
$message_type = "";

// Handle payment status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["booking_id"]) && isset($_POST["status"])) {
    $booking_id = $_POST["booking_id"];
    $status = $_POST["status"];
    
    // Debug: Print the values
    error_log("Updating event ID: " . $booking_id . " with status: " . $status);
    
    // Update both status and payment_status
    $sql = "UPDATE events SET payment_status = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $status, $booking_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Payment status updated successfully.";
            $message_type = "success";
            // Debug: Print success
            error_log("Update successful");
        } else {
            $message = "Error updating payment status: " . mysqli_error($conn);
            $message_type = "danger";
            // Debug: Print error
            error_log("Update failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Error preparing statement: " . mysqli_error($conn);
        $message_type = "danger";
        // Debug: Print error
        error_log("Statement preparation failed: " . mysqli_error($conn));
    }
}

// Fetch all events with payment details
$sql = "SELECT e.*, u.username as customer_name,
        (SELECT SUM(mi.price * e.guest_count) 
         FROM event_menu_items emi 
         JOIN menu_items mi ON emi.menu_item_id = mi.id 
         WHERE emi.event_id = e.id) as menu_total,
        COALESCE(e.payment_method, 'Not specified') as payment_method,
        DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i') as booking_datetime
        FROM events e 
        JOIN users u ON e.customer_id = u.id 
        ORDER BY e.event_date DESC";

$bookings = mysqli_query($conn, $sql);

if (!$bookings) {
    $message = "Error fetching events: " . mysqli_error($conn);
    $message_type = "danger";
    // Debug: Print error
    error_log("Error fetching events: " . mysqli_error($conn));
    
    // Check if the error is due to missing column
    if (strpos(mysqli_error($conn), "Unknown column") !== false) {
        // Try to add the missing column
        $alter_sql = "ALTER TABLE events ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL";
        if (mysqli_query($conn, $alter_sql)) {
            // Retry the original query
            $bookings = mysqli_query($conn, $sql);
            if ($bookings) {
                $message = "Database updated successfully. Please refresh the page.";
                $message_type = "success";
            }
        }
    }
}

// Debug: Check if payment_reference column exists
$sql = "SHOW COLUMNS FROM events LIKE 'payment_reference'";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) == 0) {
    error_log("payment_reference column does NOT exist in the events table");
} else {
    error_log("payment_reference column exists in the events table");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Payments - CaterSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Payment Verification</h3>
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Event Type</th>
                                        <th>Event Date</th>
                                        <th>Booking Date & Time</th>
                                        <th>Amount</th>
                                        <th>Payment Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = mysqli_fetch_assoc($bookings)): ?>
                                        <?php
                                        // Calculate total amount (menu total + staff cost)
                                        $menu_total = isset($booking["menu_total"]) ? $booking["menu_total"] : 0;
                                        $staff_cost = isset($booking["staff_cost"]) ? $booking["staff_cost"] : 0;
                                        $total_amount = $menu_total + $staff_cost;
                                        
                                        // Get payment reference
                                        $payment_reference = isset($booking["payment_reference"]) && !empty($booking["payment_reference"]) 
                                            ? $booking["payment_reference"] 
                                            : "Not provided";
                                        
                                        // Get payment method
                                        $payment_method = isset($booking["payment_method"]) ? $booking["payment_method"] : "Not specified";
                                        
                                        // Get booking date and time
                                        $booking_datetime = isset($booking["booking_datetime"]) ? $booking["booking_datetime"] : "Not available";
                                        
                                        // Debug: Print event data
                                        error_log("Event ID: " . $booking["id"] . " Payment Reference: " . $payment_reference . " Payment Method: " . $payment_method);
                                        
                                        // For wedding events, ensure transaction ID is displayed
                                        if ($booking["event_type"] == "wedding" && $payment_reference == "Not provided") {
                                            // Try to generate a transaction ID if not provided
                                            $transaction_id = "WED-" . $booking["id"] . "-" . date("Ymd");
                                            error_log("Generated transaction ID for wedding event: " . $transaction_id);
                                        } else {
                                            $transaction_id = $payment_reference;
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $booking["id"]; ?></td>
                                            <td><?php echo htmlspecialchars($booking["customer_name"]); ?></td>
                                            <td><?php echo htmlspecialchars($booking["event_type"]); ?></td>
                                            <td><?php echo htmlspecialchars($booking["event_date"]); ?></td>
                                            <td><?php echo htmlspecialchars($booking_datetime); ?></td>
                                            <td>₹<?php echo number_format($total_amount, 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    $status = isset($booking["payment_status"]) ? $booking["payment_status"] : "pending";
                                                    echo $status == "completed" ? "success" : 
                                                        ($status == "pending" ? "warning" : "danger"); 
                                                ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
                                                      method="post" 
                                                      class="d-inline ms-2" 
                                                      onsubmit="return confirmStatusUpdate(this);">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking["id"]; ?>">
                                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                                        <option value="pending" <?php echo $status == "pending" ? "selected" : ""; ?>>Pending</option>
                                                        <option value="completed" <?php echo $status == "completed" ? "selected" : ""; ?>>Completed</option>
                                                        <option value="cancelled" <?php echo $status == "cancelled" ? "selected" : ""; ?>>Cancelled</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmStatusUpdate(formElement) {
            const status = formElement.querySelector('select[name="status"]').value;
            const bookingId = formElement.querySelector('input[name="booking_id"]').value;
            
            if (confirm('Are you sure you want to update the payment status to ' + status + '?')) {
                return true;
            }
            return false;
        }
    </script>
</body>
</html> 