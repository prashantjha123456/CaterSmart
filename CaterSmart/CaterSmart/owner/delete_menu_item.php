<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: dashboard.php");
    exit;
}

$id = $_GET["id"];
$menu_item = null;
$error = null;

// First, check if the menu item exists and is not being used in any events
$sql = "SELECT m.*, COUNT(emi.menu_item_id) as usage_count 
        FROM menu_items m 
        LEFT JOIN event_menu_items emi ON m.id = emi.menu_item_id 
        WHERE m.id = ? 
        GROUP BY m.id";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            if ($row["usage_count"] > 0) {
                $error = "Cannot delete menu item as it is being used in events.";
            } else {
                $menu_item = $row;
            }
        } else {
            $error = "Menu item not found.";
        }
    }
    mysqli_stmt_close($stmt);
}

// Handle deletion confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["confirm"]) && $_POST["confirm"] == "yes") {
    // Delete the menu item
    $sql = "DELETE FROM menu_items WHERE id = ?";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION["success"] = "Menu item deleted successfully!";
            header("location: dashboard.php");
            exit;
        } else {
            $error = "Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Menu Item - CaterSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        // Tailwind configuration
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'slide-down': 'slideDown 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        slideDown: {
                            '0%': { transform: 'translateY(-10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                    },
                },
            },
        };
    </script>
    <style>
        /* Custom styles */
        .glass-effect {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        
        .dark .glass-effect {
            background-color: rgba(17, 24, 39, 0.7);
        }
        
        .light .glass-effect {
            background-color: rgba(255, 255, 255, 0.7);
        }
        
        .btn-hover {
            transition: all 0.2s ease;
        }
        
        .btn-hover:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(79, 70, 229, 0.4);
        }
        
        /* Dark mode transition */
        .dark-mode-transition {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-900 dark:to-indigo-900 min-h-screen dark-mode-transition">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 glass-effect border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors duration-300">CaterSmart</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-300">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                    
                    <!-- Dark mode toggle -->
                    <button id="darkModeToggle" class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-300">
                        <svg id="sunIcon" class="w-5 h-5 text-yellow-500 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 text-gray-700 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8 animate-fade-in">
        <div class="max-w-2xl mx-auto">
            <div class="glass-effect rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700 animate-slide-up">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Delete Menu Item</h2>
                </div>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 animate-slide-down">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400 dark:text-red-300 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800 dark:text-red-200"><?php echo $error; ?></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-6 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 animate-slide-down">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400 dark:text-yellow-300 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    Are you sure you want to delete this menu item? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Menu Item Details</h3>
                        <dl class="grid grid-cols-1 gap-2">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name:</dt>
                                <dd class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($menu_item["name"]); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category:</dt>
                                <dd class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($menu_item["category"]); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Price:</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">₹<?php echo htmlspecialchars($menu_item["price"]); ?></dd>
                            </div>
                        </dl>
                    </div>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" method="post" class="space-y-6">
                        <input type="hidden" name="confirm" value="yes">
                        <div class="flex justify-end space-x-3">
                            <a href="dashboard.php" 
                               class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-300 btn-hover">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-300 btn-hover">
                                <i class="fas fa-trash-alt mr-2"></i>Delete Menu Item
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Dark mode toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const html = document.documentElement;
            
            // Check for saved theme preference or use system preference
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
            
            // Toggle dark mode
            darkModeToggle.addEventListener('click', function() {
                if (html.classList.contains('dark')) {
                    html.classList.remove('dark');
                    localStorage.theme = 'light';
                } else {
                    html.classList.add('dark');
                    localStorage.theme = 'dark';
                }
            });
        });
    </script>
</body>
</html> 