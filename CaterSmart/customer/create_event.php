<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer") {
    header("location: ../index.php");
    exit;
}

// Get menu items for selection
$menu_items = [];
$sql = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name";
if ($result = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_items[] = $row;
    }
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

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate required fields
    $required_fields = ["event_type", "event_date", "guest_count", "payment_method"];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $_SESSION["error"] = "Please fill in all required fields: " . implode(", ", $missing_fields);
        header("location: dashboard.php");
        exit;
    }
    
    // Validate menu items
    if (!isset($_POST["menu_items"]) || empty($_POST["menu_items"])) {
        $_SESSION["error"] = "Please select at least one menu item";
        header("location: dashboard.php");
        exit;
    }
    
    // Sanitize and prepare data
    $event_type = mysqli_real_escape_string($conn, $_POST["event_type"]);
    $event_date = mysqli_real_escape_string($conn, $_POST["event_date"]);
    $guest_count = (int)$_POST["guest_count"];
    $custom_requests = isset($_POST["custom_requests"]) ? mysqli_real_escape_string($conn, $_POST["custom_requests"]) : "";
    $payment_method = mysqli_real_escape_string($conn, $_POST["payment_method"]);
    $menu_items = $_POST["menu_items"];
    
    // Get staff details
    $staff_count = (int)$_POST["staff_count"];
    $staff_type = mysqli_real_escape_string($conn, $_POST["staff_type"]);
    $event_duration = (int)$_POST["event_duration"];
    
    // Calculate staff cost based on type
    $staff_price = 0;
    switch($staff_type) {
        case 'basic':
            $staff_price = 500;
            break;
        case 'premium':
            $staff_price = 800;
            break;
        case 'luxury':
            $staff_price = 1200;
            break;
    }
    $staff_total = $staff_count * $staff_price * $event_duration;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert event
        $sql = "INSERT INTO events (customer_id, event_type, event_date, guest_count, custom_requests, status, staff_count, staff_type, event_duration, staff_cost) 
                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "issisiiid", 
                $_SESSION["id"], 
                $event_type, 
                $event_date, 
                $guest_count, 
                $custom_requests,
                $staff_count,
                $staff_type,
                $event_duration,
                $staff_total
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error creating event: " . mysqli_error($conn));
            }
            
            $event_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Insert event menu items
            $sql = "INSERT INTO event_menu_items (event_id, menu_item_id) VALUES (?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                foreach ($menu_items as $menu_item_id) {
                    mysqli_stmt_bind_param($stmt, "ii", $event_id, $menu_item_id);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error adding menu items: " . mysqli_error($conn));
                    }
                }
                mysqli_stmt_close($stmt);
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $_SESSION["success"] = "Event created successfully!";
            header("location: view_event.php?id=" . $event_id);
            exit;
            
        } else {
            throw new Exception("Error preparing statement: " . mysqli_error($conn));
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION["error"] = $e->getMessage();
        header("location: dashboard.php");
        exit;
    }
    
} else {
    // If not POST request, redirect to dashboard
    header("location: dashboard.php");
    exit;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - CaterSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="dashboard.php" class="text-xl font-bold text-gray-800">CaterSmart</a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="../logout.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION["success"])): ?>
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION["success"]; ?></span>
                </div>
                <?php unset($_SESSION["success"]); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION["error"])): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION["error"]; ?></span>
                </div>
                <?php unset($_SESSION["error"]); ?>
            <?php endif; ?>

            <!-- Create Event Form -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Create New Event</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Fill in the details to create your event.</p>
                </div>
                <div class="border-t border-gray-200">
                    <form action="process_event.php" method="POST" class="p-6">
                        <!-- Event Details -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="event_type" class="block text-sm font-medium text-gray-700">Event Type</label>
                                <select name="event_type" id="event_type" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="">Select Event Type</option>
                                    <option value="wedding">Wedding</option>
                                    <option value="birthday">Birthday</option>
                                    <option value="corporate">Corporate</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label for="event_date" class="block text-sm font-medium text-gray-700">Event Date</label>
                                <input type="date" name="event_date" id="event_date" required min="<?php echo date('Y-m-d'); ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label for="guest_count" class="block text-sm font-medium text-gray-700">Number of Guests</label>
                                <input type="number" name="guest_count" id="guest_count" required min="1" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label for="custom_requests" class="block text-sm font-medium text-gray-700">Custom Requests</label>
                                <textarea name="custom_requests" id="custom_requests" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                            </div>
                        </div>

                        <!-- Menu Items Selection -->
                        <div class="mt-8">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Select Menu Items</h4>
                            <?php foreach ($menu_items_by_category as $category => $items): ?>
                                <div class="mb-6">
                                    <h5 class="text-md font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($category); ?></h5>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <?php foreach ($items as $item): ?>
                                            <div class="relative flex items-start p-4 border rounded-lg">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center">
                                                        <input type="checkbox" name="menu_items[]" value="<?php echo $item["id"]; ?>" 
                                                               data-price="<?php echo $item["price"]; ?>"
                                                               class="menu-item-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                               onchange="calculateTotal()">
                                                        <label class="ml-3">
                                                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item["name"]); ?></span>
                                                            <p class="text-sm text-gray-500">₹<?php echo number_format($item["price"], 2); ?> per person</p>
                                                        </label>
                                                    </div>
                                                    <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($item["description"]); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Total Price Calculation -->
                        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <div class="flex justify-between items-center">
                                <h4 class="text-lg font-medium text-gray-900">Total Price</h4>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-indigo-600">₹<span id="totalPrice">0.00</span></p>
                                    <p class="text-sm text-gray-500">per person</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-600">Total for all guests: ₹<span id="totalPriceForGuests">0.00</span></p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-2"></i>Create Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today
        document.getElementById('event_date').min = new Date().toISOString().split('T')[0];

        // Calculate total price based on selected menu items and guest count
        function calculateTotal() {
            const guestCount = parseInt(document.getElementById('guest_count').value) || 0;
            const selectedItems = document.querySelectorAll('.menu-item-checkbox:checked');
            let totalPrice = 0;

            selectedItems.forEach(item => {
                totalPrice += parseFloat(item.dataset.price);
            });

            // Update per person total
            document.getElementById('totalPrice').textContent = totalPrice.toFixed(2);
            
            // Update total for all guests
            const totalForGuests = totalPrice * guestCount;
            document.getElementById('totalPriceForGuests').textContent = totalForGuests.toFixed(2);
        }

        // Add event listener for guest count changes
        document.getElementById('guest_count').addEventListener('input', calculateTotal);
    </script>
</body>
</html> 