<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer") {
    header("location: ../index.php");
    exit;
}

// Fetch user's events
$sql = "SELECT * FROM events WHERE customer_id = ? ORDER BY event_date DESC";
$events = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Fetch menu items
$sql = "SELECT * FROM menu_items ORDER BY category, name";
$menu_items = [];
if ($stmt = mysqli_prepare($conn, $sql)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $menu_items[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
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
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - CaterSmart</title>
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .menu-item-card {
            transition: all 0.3s;
        }
        
        .menu-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-900 dark:to-indigo-900 min-h-screen dark-mode-transition">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 glass-effect border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors duration-300">CaterSmart</a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="#events" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-300">My Events</a>
                    <a href="#menu" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-300">Menu</a>
                    <button onclick="showNewEventForm()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition duration-300 ease-in-out btn-hover">
                        <i class="fas fa-plus mr-2"></i>New Event
                    </button>
                    
                    <!-- Dark mode toggle -->
                    <button id="darkModeToggle" class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-300">
                        <svg id="sunIcon" class="w-5 h-5 text-yellow-500 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 text-gray-700 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                    
                    <div class="relative ml-3">
                        <div class="flex items-center">
                            <span class="text-gray-700 dark:text-gray-300 mr-2"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                            <a href="../logout.php" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-300">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="pt-16 animate-fade-in">
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="glass-effect rounded-xl shadow-md p-6 mb-6 border border-gray-200 dark:border-gray-700 animate-slide-up">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">Manage your events and explore our menu options.</p>
                    </div>
                    <div class="hidden md:block">
                        <button onclick="showNewEventForm()" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition duration-300 ease-in-out flex items-center btn-hover">
                            <i class="fas fa-plus mr-2"></i>Create New Event
                        </button>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION["success"])): ?>
                <div class="rounded-lg bg-green-50 dark:bg-green-900/30 p-4 mb-6 animate-slide-down">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400 dark:text-green-300 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200"><?php echo $_SESSION["success"]; ?></p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION["success"]); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION["error"])): ?>
                <div class="rounded-lg bg-red-50 dark:bg-red-900/30 p-4 mb-6 animate-slide-down">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400 dark:text-red-300 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200"><?php echo $_SESSION["error"]; ?></p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION["error"]); ?>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="glass-effect rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300">
                            <i class="fas fa-calendar-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Events</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo count($events); ?></p>
                        </div>
                    </div>
                </div>
                <div class="glass-effect rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed Events</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php 
                                $completed = 0;
                                foreach ($events as $event) {
                                    if (isset($event["status"]) && $event["status"] === "completed") {
                                        $completed++;
                                    }
                                }
                                echo $completed;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="glass-effect rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Upcoming Events</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php 
                                $upcoming = 0;
                                foreach ($events as $event) {
                                    if (isset($event["status"]) && $event["status"] !== "completed") {
                                        $upcoming++;
                                    }
                                }
                                echo $upcoming;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Section -->
            <div id="events" class="mb-8 animate-slide-up">
                <div class="glass-effect rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">My Events</h2>
                        <button onclick="showNewEventForm()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition duration-300 ease-in-out btn-hover">
                            <i class="fas fa-plus mr-2"></i>New Event
                        </button>
                    </div>

                    <?php if (empty($events)): ?>
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900 mb-4">
                                <i class="fas fa-calendar-plus text-3xl text-indigo-600 dark:text-indigo-300"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No events yet</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Create your first event to get started</p>
                            <button onclick="showNewEventForm()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition duration-300 ease-in-out btn-hover">
                                <i class="fas fa-plus mr-2"></i>Create Event
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($events as $event): ?>
                                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition duration-300 ease-in-out">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($event["event_type"]); ?></h3>
                                                <span class="ml-4 status-badge <?php 
                                                    $payment_status = isset($event["payment_status"]) ? $event["payment_status"] : "pending";
                                                    echo $payment_status === "completed" ? "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" : 
                                                        ($payment_status === "pending" ? "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200" : "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"); 
                                                ?>">
                                                    <?php echo ucfirst(htmlspecialchars($payment_status)); ?>
                                                </span>
                                            </div>
                                            <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                                <i class="far fa-calendar mr-2"></i>
                                                <span><?php echo date('F j, Y', strtotime($event["event_date"])); ?></span>
                                                <i class="fas fa-users ml-4 mr-2"></i>
                                                <span><?php echo htmlspecialchars($event["guest_count"]); ?> guests</span>
                                            </div>
                                            <?php if (!empty($event["custom_requests"])): ?>
                                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    <?php echo htmlspecialchars($event["custom_requests"]); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <a href="view_event.php?id=<?php echo $event['id']; ?>" 
                                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/50 hover:bg-indigo-200 dark:hover:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-300 btn-hover">
                                                <i class="fas fa-eye mr-2"></i>View Details
                                            </a>
                                            <button onclick="showDeleteConfirmation(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event["event_type"]); ?>')" 
                                                    class="ml-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/50 hover:bg-red-200 dark:hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-300 btn-hover">
                                                <i class="fas fa-trash-alt mr-2"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Menu Section -->
            <div id="menu" class="mb-8 animate-slide-up">
                <div class="glass-effect rounded-xl shadow-md p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Menu Items</h2>
                    <?php foreach ($menu_items_by_category as $category => $items): ?>
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4"><?php echo htmlspecialchars($category); ?></h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                <?php foreach ($items as $item): ?>
                                    <div class="glass-effect rounded-xl shadow-md overflow-hidden menu-item-card border border-gray-200 dark:border-gray-700">
                                        <div class="p-6">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($item["name"]); ?></h3>
                                                <span class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">₹<?php echo number_format($item["price"], 2); ?></span>
                                            </div>
                                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($item["description"]); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Event Modal -->
    <div id="newEventModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-xl bg-white dark:bg-gray-800 animate-slide-down">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Create New Event</h3>
                <button onclick="hideNewEventForm()" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors duration-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form action="create_event.php" method="post" onsubmit="return validateForm()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Event Details</h4>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="event_type">Event Type</label>
                            <select name="event_type" id="event_type" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-indigo-500 transition duration-300">
                                <option value="">Select Event Type</option>
                                <option value="wedding">Wedding</option>
                                <option value="birthday">Birthday</option>
                                <option value="corporate">Corporate</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="event_date">Event Date</label>
                            <input type="date" name="event_date" id="event_date" required min="<?php echo date('Y-m-d'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-indigo-500 transition duration-300">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="guest_count">Number of Guests</label>
                            <input type="number" name="guest_count" id="guest_count" required min="1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-indigo-500 transition duration-300" onchange="calculateTotal()">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="custom_requests">Custom Requests</label>
                            <textarea name="custom_requests" id="custom_requests" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-indigo-500 transition duration-300"></textarea>
                        </div>
                    </div>

                    <!-- Menu Selection Section -->
                    <div>
                        <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Menu Selection</h4>
                        <div class="mb-4 max-h-60 overflow-y-auto border rounded p-4 dark:border-gray-600 dark:bg-gray-700">
                            <?php foreach ($menu_items_by_category as $category => $items): ?>
                                <div class="mb-4">
                                    <h5 class="font-medium text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($category); ?></h5>
                                    <div class="space-y-2">
                                        <?php foreach ($items as $item): ?>
                                            <div class="flex items-center">
                                                <input type="checkbox" 
                                                       name="menu_items[]" 
                                                       value="<?php echo $item['id']; ?>" 
                                                       data-price="<?php echo $item['price']; ?>"
                                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded transition duration-300"
                                                       onchange="calculateTotal()">
                                                <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                                    <?php echo htmlspecialchars($item['name']); ?> 
                                                    <span class="text-indigo-600 dark:text-indigo-400">(₹<?php echo number_format($item['price'], 2); ?>)</span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Staff Selection Section -->
                <div class="mt-6 border-t pt-4 dark:border-gray-700">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Staff Selection</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="chef_count">Chefs</label>
                            <input type="number" name="chef_count" id="chef_count" min="0" value="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-indigo-500 transition duration-300" onchange="calculateTotal()">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="server_count">Servers</label>
                            <input type="number" name="server_count" id="server_count" min="0" value="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-indigo-500 transition duration-300" onchange="calculateTotal()">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="helper_count">Helpers</label>
                            <input type="number" name="helper_count" id="helper_count" min="0" value="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-indigo-500 transition duration-300" onchange="calculateTotal()">
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t pt-4 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white">Total Price</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Menu items + Staff cost</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" id="totalPrice">₹0.00</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400" id="priceBreakdown"></p>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="mt-6 border-t pt-4 dark:border-gray-700">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Payment Method</h4>
                    <div class="mb-4">
                        <select name="payment_method" id="payment_method" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-indigo-500 transition duration-300" onchange="togglePaymentDetails()">
                            <option value="">Select Payment Method</option>
                            <option value="qr">QR Code Payment</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cash">Cash on Delivery</option>
                        </select>
                    </div>

                    <!-- QR Code Payment Section -->
                    <div id="qrPaymentSection" class="mb-4 hidden">
                        <?php
                        // Get QR code from settings
                        $qr_code = "";
                        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'qr_code'";
                        if ($result = mysqli_query($conn, $sql)) {
                            if ($row = mysqli_fetch_assoc($result)) {
                                $qr_code = $row["setting_value"];
                            }
                        }
                        ?>
                        <?php if (!empty($qr_code)): ?>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Scan this QR code to make payment</p>
                                <img src="../uploads/qr/<?php echo htmlspecialchars($qr_code); ?>" 
                                     alt="Payment QR Code" 
                                     class="mx-auto mb-2 transform hover:scale-105 transition-transform duration-300" 
                                     style="max-width: 200px;">
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                                <p class="text-sm text-yellow-600 dark:text-yellow-300">QR code payment is currently not available. Please choose another payment method.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bank Transfer Section -->
                    <div id="bankTransferSection" class="mb-4 hidden">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Please transfer the amount to the following bank account:</p>
                            <div class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Bank: <span class="font-bold">State Bank of India</span></p>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Account Name: <span class="font-bold">CaterSmart</span></p>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Account Number: <span class="font-bold">1234567890</span></p>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">IFSC Code: <span class="font-bold">SBIN0001234</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Cash on Delivery Section -->
                    <div id="cashOnDeliverySection" class="mb-4 hidden">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-300">Payment will be collected on the day of the event.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" onclick="hideNewEventForm()" class="bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md mr-2 hover:bg-gray-400 dark:hover:bg-gray-600 transition duration-300 btn-hover">Cancel</button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition duration-300 btn-hover">Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white dark:bg-gray-800 animate-slide-down">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Confirm Deletion</h3>
                <button onclick="hideDeleteConfirmation()" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors duration-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <p class="text-gray-700 dark:text-gray-300 mb-4">Are you sure you want to delete the event "<span id="eventName" class="font-semibold"></span>"? This action cannot be undone.</p>
            <form id="deleteForm" action="delete_event.php" method="post">
                <input type="hidden" name="event_id" id="deleteEventId">
                <div class="flex justify-end">
                    <button type="button" onclick="hideDeleteConfirmation()" class="bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md mr-2 hover:bg-gray-400 dark:hover:bg-gray-600 transition duration-300 btn-hover">Cancel</button>
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition duration-300 btn-hover">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide new event form
        function showNewEventForm() {
            document.getElementById('newEventModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideNewEventForm() {
            document.getElementById('newEventModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Show/hide delete confirmation
        function showDeleteConfirmation(eventId, eventName) {
            document.getElementById('deleteEventId').value = eventId;
            document.getElementById('eventName').textContent = eventName;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideDeleteConfirmation() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Calculate total price
        function calculateTotal() {
            const guestCount = parseInt(document.getElementById('guest_count').value) || 0;
            const chefCount = parseInt(document.getElementById('chef_count').value) || 0;
            const serverCount = parseInt(document.getElementById('server_count').value) || 0;
            const helperCount = parseInt(document.getElementById('helper_count').value) || 0;
            
            // Menu items cost
            let menuCost = 0;
            const menuCheckboxes = document.querySelectorAll('input[name="menu_items[]"]:checked');
            menuCheckboxes.forEach(checkbox => {
                menuCost += parseFloat(checkbox.dataset.price) * guestCount;
            });
            
            // Staff cost
            const chefCost = chefCount * 1000 * guestCount / 50; // 1 chef per 50 guests
            const serverCost = serverCount * 500 * guestCount / 20; // 1 server per 20 guests
            const helperCost = helperCount * 300 * guestCount / 30; // 1 helper per 30 guests
            
            const totalCost = menuCost + chefCost + serverCost + helperCost;
            
            document.getElementById('totalPrice').textContent = '₹' + totalCost.toFixed(2);
            
            // Price breakdown
            let breakdown = '';
            if (menuCost > 0) breakdown += `Menu: ₹${menuCost.toFixed(2)}<br>`;
            if (chefCost > 0) breakdown += `Chefs: ₹${chefCost.toFixed(2)}<br>`;
            if (serverCost > 0) breakdown += `Servers: ₹${serverCost.toFixed(2)}<br>`;
            if (helperCost > 0) breakdown += `Helpers: ₹${helperCost.toFixed(2)}`;
            
            document.getElementById('priceBreakdown').innerHTML = breakdown;
        }

        // Toggle payment details
        function togglePaymentDetails() {
            const paymentMethod = document.getElementById('payment_method').value;
            const qrSection = document.getElementById('qrPaymentSection');
            const bankSection = document.getElementById('bankTransferSection');
            const cashSection = document.getElementById('cashOnDeliverySection');
            
            // Hide all sections first
            qrSection.classList.add('hidden');
            bankSection.classList.add('hidden');
            cashSection.classList.add('hidden');
            
            // Show relevant section
            if (paymentMethod === 'qr') {
                qrSection.classList.remove('hidden');
            } else if (paymentMethod === 'bank') {
                bankSection.classList.remove('hidden');
            } else if (paymentMethod === 'cash') {
                cashSection.classList.remove('hidden');
            }
        }

        // Form validation
        function validateForm() {
            const eventType = document.getElementById('event_type').value;
            const eventDate = document.getElementById('event_date').value;
            const guestCount = document.getElementById('guest_count').value;
            const paymentMethod = document.getElementById('payment_method').value;
            
            if (!eventType) {
                alert('Please select an event type');
                return false;
            }
            
            if (!eventDate) {
                alert('Please select an event date');
                return false;
            }
            
            if (!guestCount || guestCount < 1) {
                alert('Please enter a valid number of guests');
                return false;
            }
            
            // Check if at least one menu item is selected
            const menuCheckboxes = document.querySelectorAll('input[name="menu_items[]"]:checked');
            if (menuCheckboxes.length === 0) {
                alert('Please select at least one menu item');
                return false;
            }
            
            if (!paymentMethod) {
                alert('Please select a payment method');
                return false;
            }
            
            return true;
        }
        
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