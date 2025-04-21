<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

$upload_dir = "../uploads/images/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
        $filename = $_FILES["image"]["name"];
        $filetype = $_FILES["image"]["type"];
        $filesize = $_FILES["image"]["size"];
    
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
            if ($_FILES["image"]["error"] == 0) {
                // Generate unique filename
                $new_filename = "event_image_" . time() . "." . $ext;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Update database with new image filename
                    $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'event_image'";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "s", $new_filename);
                        if (mysqli_stmt_execute($stmt)) {
                            $message = "Image uploaded successfully.";
                            $message_type = "success";
                        } else {
                            $message = "Error updating database.";
                            $message_type = "danger";
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $message = "Error uploading file.";
                    $message_type = "danger";
                }
            } else {
                $message = "Error: " . $_FILES["image"]["error"];
                $message_type = "danger";
            }
        } else {
            $message = "Error: There was a problem uploading your file. Please try again.";
            $message_type = "danger";
        }
    }
}

// Get current image
$current_image = "";
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'event_image'";
if ($result = mysqli_query($conn, $sql)) {
    if ($row = mysqli_fetch_assoc($result)) {
        $current_image = $row["setting_value"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Event Image - CaterSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Upload Event Image</h2>
                <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800">Back to Dashboard</a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 rounded <?php echo $message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($current_image)): ?>
                <div class="mb-6 text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Current Image:</h3>
                    <img src="../uploads/images/<?php echo htmlspecialchars($current_image); ?>" 
                         alt="Current Event Image" 
                         class="mx-auto rounded-lg shadow-md" 
                         style="max-width: 300px;">
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                        Select Image (JPG, JPEG, PNG)
                    </label>
                    <input type="file" 
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-indigo-50 file:text-indigo-700
                                  hover:file:bg-indigo-100" 
                           id="image" 
                           name="image" 
                           accept=".jpg,.jpeg,.png" 
                           required>
                    <p class="mt-1 text-sm text-gray-500">Maximum file size: 5MB</p>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Upload Image
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 