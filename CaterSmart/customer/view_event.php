<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer") {
    header("location: ../index.php");
    exit;
}

// Check if event ID is provided
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    $_SESSION["error"] = "Invalid event ID.";
    header("location: dashboard.php");
    exit;
}

$event_id = $_GET["id"];

// Fetch event details
$sql = "SELECT id, event_type, event_date, guest_count, custom_requests, payment_status FROM events WHERE id = ? AND customer_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $event_id, $_SESSION["id"]);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($event = mysqli_fetch_assoc($result)) {
            // Event found
        } else {
            $_SESSION["error"] = "Event not found or you don't have permission to view it.";
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

// Fetch menu items
$sql = "SELECT * FROM menu_items ORDER BY category, name";
$menu_items = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $menu_items[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Group menu items by category
$menu_items_by_category = [];
foreach ($menu_items as $item) {
    $category = $item["category"];
    if (!isset($menu_items_by_category[$category])) {
        $menu_items_by_category[$category] = [];
    }
    $menu_items_by_category[$category][] = $item;
}

// Fetch selected menu items for this event
$sql = "SELECT mi.* FROM menu_items mi 
        JOIN event_menu_items emi ON mi.id = emi.menu_item_id 
        WHERE emi.event_id = ?";
$selected_items = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $selected_items[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Calculate total amount
$total_amount = 0;
foreach ($selected_items as $item) {
    $total_amount += $item["price"] * $event["guest_count"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - CaterSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-indigo-600">CaterSmart</a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2">Dashboard</a>
                        <a href="dashboard.php#menu" class="text-gray-700 hover:text-indigo-600 px-3 py-2">Menu</a>
                        <a href="../logout.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="pt-16">
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION["success"])): ?>
                <div class="rounded-md bg-green-50 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800"><?php echo $_SESSION["success"]; ?></p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION["success"]); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION["error"])): ?>
                <div class="rounded-md bg-red-50 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800"><?php echo $_SESSION["error"]; ?></p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION["error"]); ?>
            <?php endif; ?>

            <!-- Event Details -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Event Details</h3>
                </div>
                <div class="border-t border-gray-200">
                    <dl>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Event Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($event["event_type"]); ?></dd>
                        </div>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Event Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($event["event_date"]); ?></dd>
                        </div>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Number of Guests</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($event["guest_count"]); ?></dd>
                        </div>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Custom Requests</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($event["custom_requests"] ?? "None"); ?></dd>
                        </div>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Payment Status</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                    $payment_status = isset($event["payment_status"]) ? $event["payment_status"] : "pending";
                                    echo $payment_status === "completed" ? "bg-green-100 text-green-800" : 
                                        ($payment_status === "pending" ? "bg-yellow-100 text-yellow-800" : "bg-red-100 text-red-800"); 
                                    ?>">
                                    <?php echo ucfirst(htmlspecialchars($payment_status)); ?>
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Selected Menu Items -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Selected Menu Items</h3>
                </div>
                <div class="border-t border-gray-200">
                    <?php if (empty($selected_items)): ?>
                        <div class="px-4 py-5 sm:px-6">
                            <p class="text-gray-500">No menu items selected yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="px-4 py-5 sm:px-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <?php foreach ($selected_items as $item): ?>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($item["name"]); ?></h4>
                                        <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($item["description"]); ?></p>
                                        <p class="mt-2 text-sm font-medium text-gray-900">₹<?php echo number_format($item["price"], 2); ?> per person</p>
                                        <p class="mt-1 text-sm text-gray-500">Category: <?php echo htmlspecialchars($item["category"]); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4 border-t pt-4">
                                <p class="text-lg font-medium text-gray-900">Total Amount: ₹<?php echo number_format($total_amount, 2); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Section -->
            <?php 
            $payment_status = isset($event["payment_status"]) ? $event["payment_status"] : "pending";
            if ($payment_status !== "completed"): 
            ?>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Complete Payment</h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <div class="px-4 py-5 sm:px-6">
                            <form action="update_payment.php" method="post">
                                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_method">Payment Method</label>
                                    <select name="payment_method" id="payment_method" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="togglePaymentDetails()">
                                        <option value="qr">QR Code Payment</option>
                                        <option value="cash">Cash on Delivery</option>
                                        <option value="bank">Bank Transfer</option>
                                    </select>
                                </div>
                                
                                <!-- QR Code Payment Section -->
                                <div id="qrPaymentSection" class="mb-4">
                                    <?php
                                    // Get QR code from settings
                                    $qr_code = "";
                                    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'qr_code'";
                                    if ($result = mysqli_query($conn, $sql)) {
                                        if ($row = mysqli_fetch_assoc($result)) {
                                            $qr_code = $row["setting_value"];
                                        }
                                    }
                                    ?>
                                    
                                    <?php if (!empty($qr_code)): ?>
                                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                                            <p class="text-sm text-gray-600 mb-2">Scan this QR code to make payment</p>
                                            <img src="../uploads/qr/<?php echo htmlspecialchars($qr_code); ?>" 
                                                 alt="Payment QR Code" 
                                                 class="mx-auto mb-2" 
                                                 style="max-width: 200px;">
                                            <p class="text-xs text-gray-500 mt-2">After scanning, please enter the transaction reference below</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                            <p class="text-sm text-yellow-600">QR code payment is currently not available. Please choose another payment method.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Bank Transfer Section -->
                                <div id="bankTransferSection" class="mb-4 hidden">
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <p class="text-sm text-gray-600 mb-2">Please transfer to our bank account:</p>
                                        <p class="text-sm font-medium">Bank: CaterSmart Bank</p>
                                        <p class="text-sm font-medium">Account: 1234567890</p>
                                        <p class="text-sm font-medium">IFSC: CATR0001234</p>
                                    </div>
                                </div>
                                
                                <!-- Cash on Delivery Section -->
                                <div id="cashOnDeliverySection" class="mb-4 hidden">
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <p class="text-sm text-gray-600">Payment will be collected on the day of the event.</p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_reference">Payment Reference</label>
                                    <input type="text" name="payment_reference" id="payment_reference" 
                                           placeholder="Enter transaction ID or reference number" 
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <p class="text-xs text-gray-500 mt-1">Required for QR and bank transfer payments</p>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                        Update Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePaymentDetails() {
            const paymentMethod = document.getElementById('payment_method').value;
            const qrSection = document.getElementById('qrPaymentSection');
            const bankSection = document.getElementById('bankTransferSection');
            const cashSection = document.getElementById('cashOnDeliverySection');
            const referenceInput = document.getElementById('payment_reference');

            // Hide all sections first
            qrSection.classList.add('hidden');
            bankSection.classList.add('hidden');
            cashSection.classList.add('hidden');

            // Show relevant section
            if (paymentMethod === 'qr') {
                qrSection.classList.remove('hidden');
                referenceInput.required = true;
            } else if (paymentMethod === 'bank') {
                bankSection.classList.remove('hidden');
                referenceInput.required = true;
            } else if (paymentMethod === 'cash') {
                cashSection.classList.remove('hidden');
                referenceInput.required = false;
            }
        }

        // Initialize payment sections on page load
        document.addEventListener('DOMContentLoaded', function() {
            togglePaymentDetails();
        });
    </script>
</body>
</html> 