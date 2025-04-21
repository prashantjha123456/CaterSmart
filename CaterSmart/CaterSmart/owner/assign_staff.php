<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

// Check if event ID is provided
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: dashboard.php");
    exit;
}

$event_id = $_GET["id"];
$event = null;
$assigned_staff = [];
$available_staff = [];

// Fetch event details
$sql = "SELECT e.*, u.username as customer_name FROM events e 
        JOIN users u ON e.customer_id = u.id 
        WHERE e.id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $event = $row;
        } else {
            $_SESSION["error"] = "Event not found.";
            header("location: dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Fetch currently assigned staff
$sql = "SELECT s.* FROM staff s 
        JOIN event_staff es ON s.id = es.staff_id 
        WHERE es.event_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $assigned_staff[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Fetch available staff (not assigned to this event)
$sql = "SELECT * FROM staff WHERE id NOT IN (
        SELECT staff_id FROM event_staff WHERE event_id = ?
        ) ORDER BY name";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $available_staff[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["assign"]) && isset($_POST["staff_id"])) {
        $staff_id = $_POST["staff_id"];
        
        // Insert staff assignment
        $sql = "INSERT INTO event_staff (event_id, staff_id) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $event_id, $staff_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["success"] = "Staff member assigned successfully!";
                header("location: assign_staff.php?id=" . $event_id);
                exit;
            } else {
                $error = "Failed to assign staff member.";
            }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST["remove"]) && isset($_POST["staff_id"])) {
        $staff_id = $_POST["staff_id"];
        
        // Remove staff assignment
        $sql = "DELETE FROM event_staff WHERE event_id = ? AND staff_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $event_id, $staff_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["success"] = "Staff member removed successfully!";
                header("location: assign_staff.php?id=" . $event_id);
                exit;
            } else {
                $error = "Failed to remove staff member.";
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
    <title>Assign Staff - CaterSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Assign Staff to Event</h2>
                    <p class="mt-1 text-gray-600">
                        Event: <?php echo htmlspecialchars($event["event_type"]); ?><br>
                        Date: <?php echo htmlspecialchars($event["event_date"]); ?><br>
                        Customer: <?php echo htmlspecialchars($event["customer_name"]); ?>
                    </p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION["success"])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION["success"]; ?></span>
                    </div>
                    <?php unset($_SESSION["success"]); ?>
                <?php endif; ?>

                <!-- Assigned Staff Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Assigned Staff</h3>
                    <?php if (empty($assigned_staff)): ?>
                        <p class="text-gray-500">No staff members assigned to this event.</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($assigned_staff as $staff): ?>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($staff["name"]); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($staff["role"]); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($staff["contact"]); ?></p>
                                        </div>
                                        <form method="post" class="ml-4">
                                            <input type="hidden" name="staff_id" value="<?php echo $staff["id"]; ?>">
                                            <button type="submit" name="remove" class="text-red-600 hover:text-red-900">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Available Staff Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Available Staff</h3>
                    <?php if (empty($available_staff)): ?>
                        <p class="text-gray-500">No available staff members.</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($available_staff as $staff): ?>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($staff["name"]); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($staff["role"]); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($staff["contact"]); ?></p>
                                        </div>
                                        <form method="post" class="ml-4">
                                            <input type="hidden" name="staff_id" value="<?php echo $staff["id"]; ?>">
                                            <button type="submit" name="assign" class="text-indigo-600 hover:text-indigo-900">Assign</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-6">
                    <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-900">← Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 