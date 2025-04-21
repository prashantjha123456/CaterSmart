<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "owner") {
    header("location: ../index.php");
    exit;
}

// Fetch inventory items
$inventory_sql = "SELECT * FROM inventory ORDER BY item_name";
$inventory_result = mysqli_query($conn, $inventory_sql);
$inventory_items = mysqli_fetch_all($inventory_result, MYSQLI_ASSOC);

// Fetch menu items
$menu_sql = "SELECT * FROM menu_items ORDER BY category, name";
$menu_result = mysqli_query($conn, $menu_sql);
$menu_items = mysqli_fetch_all($menu_result, MYSQLI_ASSOC);

// Fetch staff members
$staff_sql = "SELECT * FROM staff ORDER BY name";
$staff_result = mysqli_query($conn, $staff_sql);
$staff_members = mysqli_fetch_all($staff_result, MYSQLI_ASSOC);

// Fetch events
$events_sql = "SELECT e.*, u.username as customer_name 
               FROM events e 
               JOIN users u ON e.customer_id = u.id 
               ORDER BY e.event_date DESC";
$events_result = mysqli_query($conn, $events_sql);
$events = mysqli_fetch_all($events_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - CaterSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
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
                    backdropBlur: {
                        xs: '2px',
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
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .btn-hover {
            transition: all 0.2s ease;
        }
        
        .btn-hover:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(79, 70, 229, 0.4);
        }
        
        .dark .glass-effect {
            background-color: rgba(17, 24, 39, 0.7);
        }
        
        .light .glass-effect {
            background-color: rgba(255, 255, 255, 0.7);
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Dark mode transition */
        .dark-mode-transition {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-900 dark:to-indigo-900 min-h-screen dark-mode-transition">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 glass-effect border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors duration-300">CaterSmart</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 dark:text-gray-300">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                    
                    <!-- Dark mode toggle -->
                    <button id="darkModeToggle" class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-300">
                        <svg id="sunIcon" class="w-5 h-5 text-yellow-500 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 text-gray-700 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                    
                    <a href="../logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-300 btn-hover">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 animate-fade-in">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION["success"])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4 animate-slide-down" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION["success"]; ?></span>
            </div>
            <?php unset($_SESSION["success"]); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION["error"])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 animate-slide-down" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION["error"]; ?></span>
            </div>
            <?php unset($_SESSION["error"]); ?>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <a href="add_inventory.php" class="glass-effect p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 card-hover border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Inventory</h3>
                <p class="text-gray-600 dark:text-gray-300">Add new items to inventory</p>
            </a>
            <a href="add_menu_item.php" class="glass-effect p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 card-hover border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Menu Item</h3>
                <p class="text-gray-600 dark:text-gray-300">Create new menu items</p>
            </a>
            <a href="add_staff.php" class="glass-effect p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 card-hover border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Staff</h3>
                <p class="text-gray-600 dark:text-gray-300">Register new staff members</p>
            </a>
            <a href="upload_image.php" class="glass-effect p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 card-hover border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Event Image</h3>
                <p class="text-gray-600 dark:text-gray-300">Manage event images</p>
            </a>
        </div>

        <!-- Event Image Section -->
        <div class="glass-effect rounded-xl shadow-md p-6 mb-8 border border-gray-200 dark:border-gray-700 animate-slide-up">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Event Image</h2>
                <a href="upload_image.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-300 btn-hover">Upload New Image</a>
            </div>
            <?php
            // Get current event image
            $event_image = "";
            $sql = "SELECT setting_value FROM settings WHERE setting_key = 'event_image'";
            if ($result = mysqli_query($conn, $sql)) {
                if ($row = mysqli_fetch_assoc($result)) {
                    $event_image = $row["setting_value"];
                }
            }
            
            if (!empty($event_image)): ?>
                <div class="text-center">
                    <img src="../uploads/images/<?php echo htmlspecialchars($event_image); ?>" 
                         alt="Event Image" 
                         class="mx-auto rounded-lg shadow-md transform hover:scale-105 transition-transform duration-300" 
                         style="max-width: 400px;">
                </div>
            <?php else: ?>
                <div class="text-center p-8 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-gray-600 dark:text-gray-300">No event image uploaded yet.</p>
                    <a href="upload_image.php" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors duration-300">Upload an image now</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment Section -->
        <div class="glass-effect rounded-xl shadow-md p-6 mb-8 border border-gray-200 dark:border-gray-700 animate-slide-up">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Payment Management</h2>
                <div class="space-x-2">
                    <a href="upload_qr.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-300 btn-hover">Upload QR Code</a>
                    <a href="verify_payments.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors duration-300 btn-hover">Verify Payments</a>
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Payment QR Code</h3>
                <?php
                $qr_code = null;
                $sql = "SELECT setting_value FROM settings WHERE setting_key = 'qr_code'";
                $result = mysqli_query($conn, $sql);
                
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $qr_code = $row["setting_value"];
                }
                
                if (!empty($qr_code)) {
                    echo '<img src="../uploads/qr/' . htmlspecialchars($qr_code) . '" alt="Payment QR Code" class="max-w-xs mx-auto transform hover:scale-105 transition-transform duration-300">';
                } else {
                    echo '<p class="text-gray-600 dark:text-gray-300">No QR code uploaded yet. <a href="upload_qr.php" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors duration-300">Upload one now</a></p>';
                }
                ?>
            </div>
        </div>

        <!-- Inventory Section -->
        <div class="glass-effect rounded-xl shadow-md p-6 mb-8 border border-gray-200 dark:border-gray-700 animate-slide-up">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Inventory</h2>
                <a href="add_inventory.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-300 btn-hover">Add Item</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($inventory_items as $item): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($item["item_name"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($item["quantity"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($item["unit"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="update_inventory.php?id=<?php echo $item["id"]; ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3 transition-colors duration-300">Update</a>
                                    <a href="delete_inventory.php?id=<?php echo $item["id"]; ?>" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-300" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Menu Items Section -->
        <div class="glass-effect rounded-xl shadow-md p-6 mb-8 border border-gray-200 dark:border-gray-700 animate-slide-up">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Menu Items</h2>
                <a href="add_menu_item.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-300 btn-hover">Add Item</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($menu_items as $item): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($item["name"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($item["category"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">₹<?php echo number_format($item["price"], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="edit_menu_item.php?id=<?php echo $item["id"]; ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3 transition-colors duration-300">Edit</a>
                                    <a href="delete_menu_item.php?id=<?php echo $item["id"]; ?>" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-300" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Staff Section -->
        <div class="glass-effect rounded-xl shadow-md p-6 mb-8 border border-gray-200 dark:border-gray-700 animate-slide-up">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Staff Members</h2>
                <a href="add_staff.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-300 btn-hover">Add Staff</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($staff_members as $staff): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($staff["name"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($staff["role"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($staff["contact"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="edit_staff.php?id=<?php echo $staff["id"]; ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3 transition-colors duration-300">Edit</a>
                                    <a href="delete_staff.php?id=<?php echo $staff["id"]; ?>" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors duration-300" onclick="return confirm('Are you sure you want to delete this staff member?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Events Section -->
        <div class="glass-effect rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700 animate-slide-up">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Upcoming Events</h2>
                <a href="view_events.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-300 btn-hover">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Event Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($events as $event): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($event["event_type"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($event["customer_name"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo htmlspecialchars($event["event_date"]); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $event["status"] === 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                            ($event["status"] === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                            ($event["status"] === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200')); ?>">
                                        <?php echo ucfirst(htmlspecialchars($event["status"])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="view_event.php?id=<?php echo $event["id"]; ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors duration-300">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Dark mode toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const html = document.documentElement;
            const sunIcon = document.getElementById('sunIcon');
            const moonIcon = document.getElementById('moonIcon');
            
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
            
            // Add animation to cards on scroll
            const cards = document.querySelectorAll('.animate-slide-up');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html> 