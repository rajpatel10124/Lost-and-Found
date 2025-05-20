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

// Determine if we're viewing lost or found items
$type = isset($_GET['type']) && $_GET['type'] === 'found' ? 'found' : 'lost';
$table = $type . '_items';
$date_field = 'date_' . $type;

// Check if the table exists
$result = $conn->query("SHOW TABLES LIKE '$table'");
if ($result->num_rows == 0) {
    // Table doesn't exist, create it
    if ($type === 'lost') {
        $sql = "CREATE TABLE lost_items (
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
    } else {
        $sql = "CREATE TABLE found_items (
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
    }
    
    if ($conn->query($sql) !== TRUE) {
        die("Error creating table: " . $conn->error);
    }
}

// Get items from database
$items = [];
$sql = "SELECT * FROM $table ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

$conn->close();

$title = ($type === 'lost') ? 'Lost Items' : 'Found Items';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Lost and Found Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
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
            margin-bottom: 20px;
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
            margin-bottom: 20px;
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
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
            height: 200px;
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
            margin-bottom: 10px;
        }
        .item-details p {
            margin: 5px 0;
            color: #666;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .no-items {
            text-align: center;
            padding: 50px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .tab-links {
            display: flex;
            margin-bottom: 20px;
        }
        .tab-link {
            padding: 10px 20px;
            background-color: #f1f1f1;
            text-decoration: none;
            color: #333;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }
        .tab-link.active {
            background-color: #4CAF50;
            color: white;
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
        <div class="section-header">
            <h1><?php echo $title; ?></h1>
            <a href="report-<?php echo $type; ?>.php" class="btn">Report <?php echo ucfirst($type); ?> Item</a>
        </div>
        
        <div class="tab-links">
            <a href="view-items.php?type=lost" class="tab-link <?php echo $type === 'lost' ? 'active' : ''; ?>">Lost Items</a>
            <a href="view-items.php?type=found" class="tab-link <?php echo $type === 'found' ? 'active' : ''; ?>">Found Items</a>
        </div>
        
        <?php if (empty($items)): ?>
            <div class="no-items">
                <p>No <?php echo $type; ?> items found.</p>
                <a href="report-<?php echo $type; ?>.php" class="btn">Report a <?php echo ucfirst($type); ?> Item</a>
            </div>
        <?php else: ?>
            <div class="items-grid">
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-image">
                            <img src="<?php echo $item['image'] ? 'uploads/' . $item['image'] : 'https://via.placeholder.com/300x200?text=No+Image'; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        </div>
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                            <p><strong>Date <?php echo ucfirst($type); ?>:</strong> <?php echo date('M d, Y', strtotime($item[$date_field])); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($item['contact_name']); ?></p>
                            <a href="item-details.php?type=<?php echo $type; ?>&id=<?php echo $item['id']; ?>" class="btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Lost and Found Portal. All rights reserved.</p>
    </footer>
</body>
</html>
