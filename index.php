<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Simple configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'lost_and_found';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "<div style='display:none;'>Database created successfully</div>";
} 

// Select database
$conn->select_db($db_name);

// Create lost_items table if not exists
$sql = "CREATE TABLE IF NOT EXISTS lost_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    date_lost DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    contact_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<div style='display:none;'>Lost items table created successfully</div>";
}

// Create found_items table if not exists
$sql = "CREATE TABLE IF NOT EXISTS found_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    date_found DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    contact_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<div style='display:none;'>Found items table created successfully</div>";
}

// Get recent lost items
$recentLostItems = [];
$sql = "SELECT * FROM lost_items ORDER BY created_at DESC LIMIT 6";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recentLostItems[] = $row;
    }
}

// Get recent found items
$recentFoundItems = [];
$sql = "SELECT * FROM found_items ORDER BY created_at DESC LIMIT 6";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recentFoundItems[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost and Found Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-info span {
            margin-right: 15px;
        }
        .logout-btn {
            background-color: #fff;
            color: #4CAF50;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        .logout-btn:hover {
            background-color: #f1f1f1;
        }
        nav {
            display: flex;
            justify-content: center;
            background-color: #333;
            padding: 10px;
        }
        nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            margin: 0 5px;
        }
        nav a:hover {
            background-color: #555;
        }
        .hero {
            background-color: #fff;
            padding: 50px 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: #666;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-secondary {
            background-color: #2196F3;
        }
        .btn-secondary:hover {
            background-color: #0b7dda;
        }
        .section-title {
            text-align: center;
            margin: 30px 0;
        }
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            background-color: #f1f1f1;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .tab-btn.active {
            background-color: #4CAF50;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .item-card {
            background-color: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .item-image {
            height: 180px;
            overflow: hidden;
        }
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .item-details {
            padding: 15px;
        }
        .item-details h3 {
            margin-top: 0;
        }
        .item-details p {
            margin: 5px 0;
            color: #666;
        }
        .no-items {
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .steps {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        .step {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .step-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #4CAF50;
        }
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Lost and Found Portal</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <nav>
        <a href="index.php">Home</a>
        <a href="view-items.php?type=lost">Lost Items</a>
        <a href="view-items.php?type=found">Found Items</a>
        <a href="report-lost.php">Report Lost</a>
        <a href="report-found.php">Report Found</a>
    </nav>
    
    <div class="container">
        <section class="hero">
            <h1>Lost Something? Found Something?</h1>
            <p>Our platform helps connect people who have lost items with those who have found them.</p>
            <div class="hero-buttons">
                <a href="report-lost.php" class="btn">Report Lost Item</a>
                <a href="report-found.php" class="btn btn-secondary">Report Found Item</a>
            </div>
        </section>

        <section>
            <h2 class="section-title">Recent Reports</h2>
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('lost')">Lost Items</button>
                <button class="tab-btn" onclick="openTab('found')">Found Items</button>
            </div>
            
            <div id="lost" class="tab-content active">
                <div class="items-grid">
                    <?php
                    if (count($recentLostItems) > 0) {
                        foreach ($recentLostItems as $item) {
                            echo '<div class="item-card">';
                            echo '<div class="item-image">';
                            echo '<img src="' . ($item['image'] ? 'uploads/' . $item['image'] : 'https://via.placeholder.com/300x200?text=No+Image') . '" alt="' . htmlspecialchars($item['item_name']) . '">';
                            echo '</div>';
                            echo '<div class="item-details">';
                            echo '<h3>' . htmlspecialchars($item['item_name']) . '</h3>';
                            echo '<p><strong>Location:</strong> ' . htmlspecialchars($item['location']) . '</p>';
                            echo '<p><strong>Date Lost:</strong> ' . date('M d, Y', strtotime($item['date_lost'])) . '</p>';
                            echo '<a href="item-details.php?type=lost&id=' . $item['id'] . '" class="btn">View Details</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="no-items">No lost items reported yet.</p>';
                    }
                    ?>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="view-items.php?type=lost" class="btn">View All Lost Items</a>
                </div>
            </div>
            
            <div id="found" class="tab-content">
                <div class="items-grid">
                    <?php
                    if (count($recentFoundItems) > 0) {
                        foreach ($recentFoundItems as $item) {
                            echo '<div class="item-card">';
                            echo '<div class="item-image">';
                            echo '<img src="' . ($item['image'] ? 'uploads/' . $item['image'] : 'https://via.placeholder.com/300x200?text=No+Image') . '" alt="' . htmlspecialchars($item['item_name']) . '">';
                            echo '</div>';
                            echo '<div class="item-details">';
                            echo '<h3>' . htmlspecialchars($item['item_name']) . '</h3>';
                            echo '<p><strong>Location:</strong> ' . htmlspecialchars($item['location']) . '</p>';
                            echo '<p><strong>Date Found:</strong> ' . date('M d, Y', strtotime($item['date_found'])) . '</p>';
                            echo '<a href="item-details.php?type=found&id=' . $item['id'] . '" class="btn">View Details</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="no-items">No found items reported yet.</p>';
                    }
                    ?>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="view-items.php?type=found" class="btn">View All Found Items</a>
                </div>
            </div>
        </section>

        <section>
            <h2 class="section-title">How It Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon">📝</div>
                    <h3>Report</h3>
                    <p>Report your lost item or an item you've found with detailed information.</p>
                </div>
                <div class="step">
                    <div class="step-icon">🔍</div>
                    <h3>Match</h3>
                    <p>Our system will try to match lost items with found items.</p>
                </div>
                <div class="step">
                    <div class="step-icon">🤝</div>
                    <h3>Connect</h3>
                    <p>Get connected with the finder/owner to retrieve your item.</p>
                </div>
            </div>
        </section>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Lost and Found Portal. All rights reserved.</p>
    </footer>
    
    <script>
        function openTab(tabName) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].style.display = "none";
            }
            
            // Remove active class from all tab buttons
            var tabButtons = document.getElementsByClassName("tab-btn");
            for (var i = 0; i < tabButtons.length; i++) {
                tabButtons[i].className = tabButtons[i].className.replace(" active", "");
            }
            
            // Show the selected tab content and add active class to the button
            document.getElementById(tabName).style.display = "block";
            event.currentTarget.className += " active";
        }
    </script>
</body>
</html>
