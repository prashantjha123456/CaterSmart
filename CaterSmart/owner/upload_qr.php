<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

$upload_dir = "../uploads/qr/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["qr_code"]) && $_FILES["qr_code"]["error"] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
        $filename = $_FILES["qr_code"]["name"];
        $filetype = $_FILES["qr_code"]["type"];
        $filesize = $_FILES["qr_code"]["size"];
    
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) {
            $message = "Error: Please select a valid image format (JPG, JPEG, PNG).";
            $message_type = "danger";
        }
    
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $message = "Error: File size is larger than the 5MB limit.";
            $message_type = "danger";
        }
    
        // Verify MIME type of the file
        if (in_array($filetype, $allowed)) {
            // Check if there are any errors
            if ($_FILES["qr_code"]["error"] == 0) {
                // Generate unique filename
                $new_filename = "qr_code_" . time() . "." . $ext;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["qr_code"]["tmp_name"], $target_file)) {
                    // First, ensure the settings table exists
                    $create_table_sql = "CREATE TABLE IF NOT EXISTS settings (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        setting_key VARCHAR(50) UNIQUE NOT NULL,
                        setting_value TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    
                    if (!mysqli_query($conn, $create_table_sql)) {
                        $message = "Error creating settings table: " . mysqli_error($conn);
                        $message_type = "danger";
                    } else {
                        // Check if qr_code setting exists
                        $check_sql = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'qr_code'";
                        $result = mysqli_query($conn, $check_sql);
                        
                        if ($result === false) {
                            $message = "Error checking settings: " . mysqli_error($conn);
                            $message_type = "danger";
                        } else {
                            $row = mysqli_fetch_assoc($result);
                            
                            if ($row['count'] == 0) {
                                // Insert new setting if it doesn't exist
                                $insert_sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('qr_code', ?)";
                                $stmt = mysqli_prepare($conn, $insert_sql);
                                mysqli_stmt_bind_param($stmt, "s", $new_filename);
                            } else {
                                // Update existing setting
                                $insert_sql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'qr_code'";
                                $stmt = mysqli_prepare($conn, $insert_sql);
                                mysqli_stmt_bind_param($stmt, "s", $new_filename);
                            }
                            
                            if (mysqli_stmt_execute($stmt)) {
                                $message = "QR code uploaded successfully.";
                                $message_type = "success";
                            } else {
                                $message = "Error updating database: " . mysqli_error($conn);
                                $message_type = "danger";
                            }
                            mysqli_stmt_close($stmt);
                        }
                    }
                } else {
                    $message = "Error uploading file.";
                    $message_type = "danger";
                }
            } else {
                $message = "Error: " . $_FILES["qr_code"]["error"];
                $message_type = "danger";
            }
        } else {
            $message = "Error: There was a problem uploading your file. Please try again.";
            $message_type = "danger";
        }
    }
}

// Get current QR code
$current_qr = "";
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'qr_code'";
if ($result = mysqli_query($conn, $sql)) {
    if ($row = mysqli_fetch_assoc($result)) {
        $current_qr = $row["setting_value"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload QR Code - CaterSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Upload Payment QR Code</h2>
                <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800">Back to Dashboard</a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 rounded <?php echo $message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($current_qr)): ?>
                <div class="mb-6 text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Current QR Code:</h3>
                    <img src="../uploads/qr/<?php echo htmlspecialchars($current_qr); ?>" 
                         alt="Current QR Code" 
                         class="mx-auto rounded-lg shadow-md" 
                         style="max-width: 200px;">
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="qr_code">
                        Select QR Code Image (JPG, JPEG, PNG)
                    </label>
                    <input type="file" 
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-indigo-50 file:text-indigo-700
                                  hover:file:bg-indigo-100" 
                           id="qr_code" 
                           name="qr_code" 
                           accept=".jpg,.jpeg,.png" 
                           required>
                    <p class="mt-1 text-sm text-gray-500">Maximum file size: 5MB</p>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Upload QR Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 