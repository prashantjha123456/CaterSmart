<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

// Get inventory item ID
$item_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$item_id) {
    $_SESSION["error"] = "Invalid inventory item.";
    header("location: dashboard.php");
    exit;
}

// Fetch inventory item details
$sql = "SELECT * FROM inventory WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $item_name = $row['item_name'];
        $quantity = $row['quantity'];
        $unit = $row['unit'];
    } else {
        $_SESSION["error"] = "Inventory item not found.";
        header("location: dashboard.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $new_quantity = trim($_POST["quantity"]);
    $action = trim($_POST["action"]); // 'add' or 'subtract'
    $notes = trim($_POST["notes"]);

    // Validate quantity
    if (!is_numeric($new_quantity) || $new_quantity <= 0) {
        $_SESSION["error"] = "Please enter a valid quantity.";
    } else {
        // Calculate new quantity based on action
        $quantity_change = $action === 'add' ? $new_quantity : -$new_quantity;
        $new_total = $quantity + $quantity_change;

        if ($new_total < 0) {
            $_SESSION["error"] = "Insufficient quantity available.";
        } else {
            // Update inventory quantity
            $update_sql = "UPDATE inventory SET quantity = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($stmt, "di", $new_total, $item_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Log the inventory change
                    $log_sql = "INSERT INTO inventory_logs (inventory_id, action, quantity_change, notes, created_at) VALUES (?, ?, ?, ?, NOW())";
                    if ($log_stmt = mysqli_prepare($conn, $log_sql)) {
                        mysqli_stmt_bind_param($log_stmt, "isds", $item_id, $action, $quantity_change, $notes);
                        mysqli_stmt_execute($log_stmt);
                        mysqli_stmt_close($log_stmt);
                    }
                    
                    $_SESSION["success"] = "Inventory updated successfully!";
                    header("location: dashboard.php");
                    exit;
                } else {
                    $_SESSION["error"] = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Inventory - CaterSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Update Inventory</h2>
            
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-700"><?php echo htmlspecialchars($item_name); ?></h3>
                <p class="text-gray-600">Current Quantity: <?php echo $quantity . ' ' . $unit; ?></p>
            </div>
            
            <?php if (isset($_SESSION["error"])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION["error"]; ?></span>
                </div>
                <?php unset($_SESSION["error"]); ?>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $item_id); ?>" method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Action</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="action" value="add" checked class="form-radio h-4 w-4 text-indigo-600">
                            <span class="ml-2">Add Stock</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="action" value="subtract" class="form-radio h-4 w-4 text-indigo-600">
                            <span class="ml-2">Remove Stock</span>
                        </label>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" step="0.01" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="notes">Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>
                
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Update Inventory
                    </button>
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 