<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

$id = $item_name = $quantity = $unit = "";
$item_name_err = $quantity_err = "";

// Check if ID parameter exists
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    $_SESSION["error"] = "Invalid inventory item ID.";
    header("location: dashboard.php");
    exit;
}

$id = $_GET["id"];

// Fetch existing inventory item
$sql = "SELECT * FROM inventory WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $item_name = $row["item_name"];
            $quantity = $row["quantity"];
            $unit = $row["unit"];
        } else {
            $_SESSION["error"] = "Inventory item not found.";
            header("location: dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate item name
    if (empty(trim($_POST["item_name"]))) {
        $item_name_err = "Please enter item name.";
    } else {
        $item_name = trim($_POST["item_name"]);
    }
    
    // Validate quantity
    if (empty(trim($_POST["quantity"]))) {
        $quantity_err = "Please enter quantity.";
    } elseif (!is_numeric(trim($_POST["quantity"])) || trim($_POST["quantity"]) <= 0) {
        $quantity_err = "Please enter a valid quantity.";
    } else {
        $quantity = trim($_POST["quantity"]);
    }
    
    // Get unit
    $unit = trim($_POST["unit"]);
    
    // Check input errors before updating the database
    if (empty($item_name_err) && empty($quantity_err)) {
        $sql = "UPDATE inventory SET item_name = ?, quantity = ?, unit = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sisi", $item_name, $quantity, $unit, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["success"] = "Inventory item updated successfully.";
                header("location: dashboard.php");
                exit;
            } else {
                $_SESSION["error"] = "Error updating inventory item.";
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
    <title>Edit Inventory Item - CaterSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Edit Inventory Item</h3>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" method="post">
                            <div class="mb-3">
                                <label for="item_name" class="form-label">Item Name</label>
                                <input type="text" class="form-control <?php echo (!empty($item_name_err)) ? 'is-invalid' : ''; ?>" 
                                       id="item_name" name="item_name" value="<?php echo $item_name; ?>">
                                <div class="invalid-feedback"><?php echo $item_name_err; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" step="0.01" class="form-control <?php echo (!empty($quantity_err)) ? 'is-invalid' : ''; ?>" 
                                       id="quantity" name="quantity" value="<?php echo $quantity; ?>">
                                <div class="invalid-feedback"><?php echo $quantity_err; ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <select class="form-select" id="unit" name="unit">
                                    <option value="kg" <?php echo ($unit == "kg") ? "selected" : ""; ?>>Kilograms (kg)</option>
                                    <option value="g" <?php echo ($unit == "g") ? "selected" : ""; ?>>Grams (g)</option>
                                    <option value="l" <?php echo ($unit == "l") ? "selected" : ""; ?>>Liters (L)</option>
                                    <option value="ml" <?php echo ($unit == "ml") ? "selected" : ""; ?>>Milliliters (mL)</option>
                                    <option value="pcs" <?php echo ($unit == "pcs") ? "selected" : ""; ?>>Pieces (pcs)</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Item</button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
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