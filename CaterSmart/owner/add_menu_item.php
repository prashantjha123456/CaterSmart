<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $category = trim($_POST["category"]);

    // Validate required fields
    if (empty($name) || empty($price) || empty($category)) {
        $_SESSION["error"] = "Please fill all required fields.";
        header("location: dashboard.php");
        exit;
    }

    // Validate price
    if (!is_numeric($price) || $price <= 0) {
        $_SESSION["error"] = "Please enter a valid price.";
        header("location: dashboard.php");
        exit;
    }

    // Insert menu item into database
    $sql = "INSERT INTO menu_items (name, description, price, category) VALUES (?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssds", $name, $description, $price, $category);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION["success"] = "Menu item added successfully!";
            header("location: dashboard.php");
            exit;
        } else {
            $_SESSION["error"] = "Something went wrong. Please try again later.";
            header("location: dashboard.php");
            exit;
        }

        mysqli_stmt_close($stmt);
    }
} else {
    // If not POST request, show the form
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Menu Item - CaterSmart</title>
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
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Add Menu Item</h2>
                </div>

                <?php if (isset($_SESSION["error"])): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 animate-slide-down">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400 dark:text-red-300 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                    <?php 
                                    echo $_SESSION["error"];
                                    unset($_SESSION["error"]);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Item Name *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               required 
                               class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 transition-colors duration-300">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="3" 
                                  class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 transition-colors duration-300"></textarea>
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price (₹) *</label>
                        <div class="mt-1 relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400">₹</span>
                            </div>
                            <input type="number" 
                                   id="price" 
                                   name="price" 
                                   step="0.01" 
                                   min="0" 
                                   required 
                                   class="pl-7 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 transition-colors duration-300">
                        </div>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category *</label>
                        <select id="category" 
                                name="category" 
                                required 
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 transition-colors duration-300">
                            <option value="">Select a category</option>
                            <option value="appetizers">Appetizers</option>
                            <option value="main_course">Main Course</option>
                            <option value="desserts">Desserts</option>
                            <option value="beverages">Beverages</option>
                            <option value="special_items">Special Items</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="dashboard.php" 
                           class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-300 btn-hover">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-300 btn-hover">
                            <i class="fas fa-plus mr-2"></i>Add Menu Item
                        </button>
                    </div>
                </form>
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
<?php
}
?> 