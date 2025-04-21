<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer") {
    header("location: ../index.php");
    exit;
}

// Check if booking ID is provided
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    $_SESSION["error"] = "Invalid booking ID.";
    header("location: dashboard.php");
    exit;
}

$booking_id = $_GET["id"];
$booking = null;
$qr_code = "";

// Fetch booking details
$sql = "SELECT b.*, e.event_type, e.event_date, e.guest_count, e.total_amount 
        FROM bookings b 
        JOIN events e ON b.event_id = e.id 
        WHERE b.id = ? AND b.customer_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION["id"]);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $booking = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

if (!$booking) {
    $_SESSION["error"] = "Booking not found.";
    header("location: dashboard.php");
    exit;
}

// Fetch QR code
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'qr_code'";
if ($result = mysqli_query($conn, $sql)) {
    if ($row = mysqli_fetch_assoc($result)) {
        $qr_code = $row["setting_value"];
    }
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $transaction_id = trim($_POST["transaction_id"]);
    $payment_date = trim($_POST["payment_date"]);
    
    if (empty($transaction_id) || empty($payment_date)) {
        $message = "Please fill in all fields.";
        $message_type = "danger";
    } else {
        $sql = "UPDATE bookings SET 
                payment_status = 'Pending Verification',
                transaction_id = ?,
                payment_date = ?
                WHERE id = ?";
                
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $transaction_id, $payment_date, $booking_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["success"] = "Payment details submitted successfully. Please wait for verification.";
                header("location: dashboard.php");
                exit;
            } else {
                $message = "Error submitting payment details.";
                $message_type = "danger";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - CaterSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Payment Details</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <div class="booking-details mb-4">
                            <h5>Booking Summary</h5>
                            <p><strong>Event Type:</strong> <?php echo htmlspecialchars($booking["event_type"]); ?></p>
                            <p><strong>Event Date:</strong> <?php echo htmlspecialchars($booking["event_date"]); ?></p>
                            <p><strong>Guest Count:</strong> <?php echo htmlspecialchars($booking["guest_count"]); ?></p>
                            <p><strong>Total Amount:</strong> ₹<?php echo number_format($booking["total_amount"], 2); ?></p>
                        </div>

                        <?php if (!empty($qr_code)): ?>
                            <div class="text-center mb-4">
                                <h5>Scan QR Code to Pay</h5>
                                <img src="../uploads/qr/<?php echo htmlspecialchars($qr_code); ?>" 
                                     alt="Payment QR Code" class="img-fluid mb-3" style="max-width: 200px;">
                                <p class="text-muted">Scan this QR code using any UPI payment app to make the payment.</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                QR code not available. Please contact the restaurant for payment instructions.
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $booking_id); ?>" method="post">
                            <div class="mb-3">
                                <label for="transaction_id" class="form-label">UPI Transaction ID</label>
                                <input type="text" class="form-control" id="transaction_id" name="transaction_id" required>
                                <div class="form-text">Enter the transaction ID from your payment app</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Payment Date</label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Submit Payment Details</button>
                                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 