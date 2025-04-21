<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $item_name = trim($_POST["item_name"]);
    $quantity = trim($_POST["quantity"]);
    $unit = trim($_POST["unit"]);

    // Validate required fields
    if (empty($item_name) || empty($quantity) || empty($unit)) {
        $_SESSION["error"] = "Please fill all required fields.";
        header("location: dashboard.php");
        exit;
    }

    // Validate quantity
    if (!is_numeric($quantity) || $quantity <= 0) {
        $_SESSION["error"] = "Please enter a valid quantity.";
        header("location: dashboard.php");
        exit;
    }

    // Insert inventory item into database
    $sql = "INSERT INTO inventory (item_name, quantity, unit) VALUES (?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sds", $item_name, $quantity, $unit);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION["success"] = "Inventory item added successfully!";
            header("location: dashboard.php");
            exit;
        } else {
            $_SESSION["error"] = "Something went wrong. Please try again later.";
            header("location: dashboard.php");
            exit;
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Inventory Item - CaterSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Add Inventory Item</h2>
            
            <?php if (isset($_SESSION["error"])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION["error"]; ?></span>
                </div>
                <?php unset($_SESSION["error"]); ?>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="item_name">Item Name</label>
                    <input type="text" name="item_name" id="item_name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" step="0.01" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="unit">Unit</label>
                    <select name="unit" id="unit" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="kg">Kilograms (kg)</option>
                        <option value="g">Grams (g)</option>
                        <option value="L">Liters (L)</option>
                        <option value="ml">Milliliters (ml)</option>
                        <option value="pcs">Pieces (pcs)</option>
                        <option value="boxes">Boxes</option>
                        <option value="bags">Bags</option>
                    </select>
                </div>
                
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Add Item
                    </button>
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 