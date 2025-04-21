<?php
session_start();
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SESSION["role"] === "customer") {
        header("location: customer/dashboard.php");
    } else {
        header("location: owner/dashboard.php");
    }
    exit;
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    
    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        session_start();
                        
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;
                        $_SESSION["role"] = $role;
                        
                        if ($role === "customer") {
                            header("location: customer/dashboard.php");
                        } else {
                            header("location: owner/dashboard.php");
                        }
                    } else {
                        $login_err = "Invalid username or password.";
                    }
                }
            } else {
                $login_err = "Invalid username or password.";
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }

        mysqli_stmt_close($stmt);
    }
}

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $role = $_POST["role"];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $register_err = "Please fill all required fields.";
    } else {
        // Check if username exists
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $register_err = "This username is already taken.";
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $register_err = "This email is already registered.";
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        // If no errors, proceed with registration
        if (empty($register_err)) {
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $role);
                
                if (mysqli_stmt_execute($stmt)) {
                    $register_success = "Registration successful! You can now login.";
                } else {
                    $register_err = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Fetch featured menu items
$featured_items_sql = "SELECT * FROM menu_items ORDER BY RAND() LIMIT 6";
$featured_items_result = mysqli_query($conn, $featured_items_sql);
$featured_items = mysqli_fetch_all($featured_items_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CaterSmart - Professional Catering Services</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .modal-content {
            position: relative;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            max-width: 28rem;
            width: 90%;
            z-index: 1001;
        }
        .show {
            display: block;
        }
        .hero-pattern {
            background-image: url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-indigo-600">CaterSmart</a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="#features" class="text-gray-700 hover:text-indigo-600 px-3 py-2">Features</a>
                        <a href="#menu" class="text-gray-700 hover:text-indigo-600 px-3 py-2">Menu</a>
                        <a href="#about" class="text-gray-700 hover:text-indigo-600 px-3 py-2">About</a>
                        <a href="#contact" class="text-gray-700 hover:text-indigo-600 px-3 py-2">Contact</a>
                        <button onclick="showLoginForm()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Login</button>
                        <button onclick="showRegisterForm()" class="bg-white text-indigo-600 border border-indigo-600 px-4 py-2 rounded-md hover:bg-indigo-50">Register</button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="pt-16">
        <div class="relative bg-white overflow-hidden">
            <div class="max-w-7xl mx-auto">
                <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                    <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                        <div class="sm:text-center lg:text-left">
                            <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                                <span class="block">Professional Catering</span>
                                <span class="block text-indigo-600">Made Simple</span>
                            </h1>
                            <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                                Streamline your catering business or find the perfect caterer for your event. CaterSmart makes event planning effortless.
                            </p>
                            <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                                <div class="rounded-md shadow">
                                    <a href="#features" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
                                        Get Started
                                    </a>
                                </div>
                                <div class="mt-3 sm:mt-0 sm:ml-3">
                                    <a href="#menu" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 md:py-4 md:text-lg md:px-10">
                                        View Menu
                                    </a>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Features for Everyone
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    Whether you're a customer or a catering business owner, CaterSmart has you covered.
                </p>
            </div>

            <div class="mt-10">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Customer Features -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <i class="fas fa-calendar-alt text-white text-2xl"></i>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">Event Planning</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Plan your events with ease. Book caterers, manage guest lists, and track your events.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <i class="fas fa-utensils text-white text-2xl"></i>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">Menu Selection</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Browse through our extensive menu options and customize your event menu.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <i class="fas fa-credit-card text-white text-2xl"></i>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">Easy Payments</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Multiple payment options including QR code, bank transfer, and cash on delivery.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Owner Features -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <i class="fas fa-chart-line text-white text-2xl"></i>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">Business Management</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Manage your menu, inventory, and staff all in one place.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <i class="fas fa-users text-white text-2xl"></i>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">Customer Management</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Track orders, manage customer relationships, and handle event bookings.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <i class="fas fa-box text-white text-2xl"></i>
                                </div>
                                <div class="ml-5">
                                    <h3 class="text-lg font-medium text-gray-900">Inventory Control</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Keep track of your inventory and automate reordering processes.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Section -->
    <div id="menu" class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Featured Menu Items
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    Discover our delicious selection of dishes for your special events.
                </p>
            </div>

            <div class="mt-10">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($featured_items as $item): ?>
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($item["name"]); ?></h3>
                                <p class="mt-2 text-sm text-gray-500"><?php echo htmlspecialchars($item["description"]); ?></p>
                                <p class="mt-2 text-lg font-medium text-indigo-600">₹<?php echo number_format($item["price"], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div id="about" class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    About CaterSmart
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    We're revolutionizing the catering industry with our innovative platform.
                </p>
            </div>

            <div class="mt-10">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                        <h3 class="text-xl font-medium text-gray-900 mb-4">Our Mission</h3>
                        <p class="text-gray-500">
                            To simplify event planning and catering management by providing a comprehensive platform that connects customers with professional caterers.
                        </p>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                        <h3 class="text-xl font-medium text-gray-900 mb-4">Our Vision</h3>
                        <p class="text-gray-500">
                            To become the leading platform for catering services, making event planning accessible and efficient for everyone.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div id="contact" class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Contact Us
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    Have questions? We're here to help!
                </p>
            </div>

            <div class="mt-10">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                        <h3 class="text-xl font-medium text-gray-900 mb-4">Get in Touch</h3>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-indigo-600 mr-3"></i>
                                <span class="text-gray-500">support@catersmart.com</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone text-indigo-600 mr-3"></i>
                                <span class="text-gray-500">+91 1234567890</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-indigo-600 mr-3"></i>
                                <span class="text-gray-500">123 Catering Street, Food City, FC 12345</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                        <h3 class="text-xl font-medium text-gray-900 mb-4">Business Hours</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Monday - Friday</span>
                                <span class="text-gray-900">9:00 AM - 6:00 PM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Saturday</span>
                                <span class="text-gray-900">10:00 AM - 4:00 PM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Sunday</span>
                                <span class="text-gray-900">Closed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Login</h3>
                <button onclick="hideLoginForm()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php if (isset($login_err)): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <?php echo $login_err; ?>
                </div>
            <?php endif; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                    <input type="text" name="username" id="username" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <input type="password" name="password" id="password" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" name="login" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Register</h3>
                <button onclick="hideRegisterForm()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php if (isset($register_err)): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <?php echo $register_err; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($register_success)): ?>
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <?php echo $register_success; ?>
                </div>
            <?php endif; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                    <input type="text" name="username" id="username" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                    <input type="email" name="email" id="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <input type="password" name="password" id="password" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="role">Role</label>
                    <select name="role" id="role" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="customer">Customer</option>
                        <option value="owner">Catering Business Owner</option>
                    </select>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" name="register" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">CaterSmart</h3>
                    <p class="text-gray-400">
                        Making catering management simple and efficient for everyone.
                    </p>
                </div>
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white">Features</a></li>
                        <li><a href="#menu" class="text-gray-400 hover:text-white">Menu</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white">About</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 pt-8">
                <p class="text-center text-gray-400">
                    &copy; <?php echo date("Y"); ?> CaterSmart. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        function showLoginForm() {
            document.getElementById('loginModal').classList.add('show');
        }

        function hideLoginForm() {
            document.getElementById('loginModal').classList.remove('show');
        }

        function showRegisterForm() {
            document.getElementById('registerModal').classList.add('show');
        }

        function hideRegisterForm() {
            document.getElementById('registerModal').classList.remove('show');
        }
    </script>
</body>
</html> 