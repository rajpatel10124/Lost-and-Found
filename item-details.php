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
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get item type and ID from URL
$type = isset($_GET['type']) && $_GET['type'] === 'found' ? 'found' : 'lost';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    // Invalid ID, redirect to items list
    header("Location: view-items.php?type=$type");
    exit;
}

$table = $type . '_items';
$date_field = 'date_' . $type;

// Get item details
$item = null;
$sql = "SELECT * FROM $table WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $item = $result->fetch_assoc();
} else {
    // Item not found, redirect to items list
    header("Location: view-items.php?type=$type");
    exit;
}

// Check if the user is the owner of this item
$isOwner = ($item['contact_email'] === $_SESSION['username']);

// Admin can delete any item
if ($_SESSION['username'] === 'admin') {
    $isOwner = true;
}

// Debug information to help troubleshoot
$debug_info = "<!-- Debug: User: " . $_SESSION['username'] . ", Item owner: " . $item['contact_email'] . ", isOwner: " . ($isOwner ? 'true' : 'false') . " -->";

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Details - Lost and Found Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
        .item-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .item-image {
            flex: 1;
            min-width: 300px;
        }
        .item-image img {
            width: 100%;
            border-radius: 5px;
        }
        .item-info {
            flex: 2;
            min-width: 300px;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-group h3 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .contact-actions {
            margin-top: 20px;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
        }
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            color: #666;
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
        <a href="matches.php">Matches</a>
        <a href="report-lost.php">Report Lost</a>
        <a href="report-found.php">Report Found</a>
    </nav>
    
    <div class="container">
        <h1><?php echo htmlspecialchars($item['item_name']); ?></h1>
        
        <div class="item-details">
            <div class="item-image">
                <img src="<?php echo $item['image'] ? 'uploads/' . $item['image'] : 'https://via.placeholder.com/300x200?text=No+Image'; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
            </div>
            
            <div class="item-info">
                <div class="info-group">
                    <h3>Item Information</h3>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span><?php echo ucfirst($type); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date <?php echo ucfirst($type); ?>:</span>
                        <span><?php echo date('F d, Y', strtotime($item[$date_field])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location:</span>
                        <span><?php echo htmlspecialchars($item['location']); ?></span>
                    </div>
                </div>
                
                <div class="info-group">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                </div>
                
                <div class="info-group">
                    <h3>Contact Information</h3>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span><?php echo htmlspecialchars($item['contact_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span><?php echo htmlspecialchars($item['contact_email']); ?></span>
                    </div>
                    <?php if (!empty($item['contact_phone'])): ?>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span><?php echo htmlspecialchars($item['contact_phone']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="contact-actions">
                    <a href="mailto:<?php echo htmlspecialchars($item['contact_email']); ?>?subject=Regarding your <?php echo $type; ?> item: <?php echo htmlspecialchars($item['item_name']); ?>" class="btn">Contact by Email</a>
                    <?php if (!empty($item['contact_phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($item['contact_phone']); ?>" class="btn">Call</a>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <a href="view-items.php?type=<?php echo $type; ?>" class="btn">Back to <?php echo ucfirst($type); ?> Items</a>
                    
                    <?php if ($isOwner): ?>
                        <!-- Try both delete-item.php and DeleteItem.php to handle case sensitivity issues -->
                        <a href="delete-item.php?type=<?php echo $type; ?>&id=<?php echo $item['id']; ?>" class="btn btn-danger">Delete This Item</a>
                        <!-- Alternative delete link -->
                        <a href="DeleteItem.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>" class="btn btn-danger" style="display:none;">Delete Item (Alt)</a>
                    <?php endif; ?>
                </div>
                
                <!-- Debug information -->
                <div class="debug-info">
                    <p>Debug Info:</p>
                    <p>User: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <p>Item Owner: <?php echo htmlspecialchars($item['contact_email']); ?></p>
                    <p>Is Owner: <?php echo $isOwner ? 'Yes' : 'No'; ?></p>
                    <p>Item ID: <?php echo $id; ?></p>
                    <p>Item Type: <?php echo $type; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Lost and Found Portal. All rights reserved.</p>
    </footer>
    <?php echo $debug_info; ?>
    
    <script>
    // This script will try the alternative delete link if the first one fails
    document.addEventListener('DOMContentLoaded', function() {
        // Get the primary delete button
        var primaryDeleteBtn = document.querySelector('a[href^="delete-item.php"]');
        
        if (primaryDeleteBtn) {
            primaryDeleteBtn.addEventListener('click', function(e) {
                // Store the original link
                var originalHref = this.getAttribute('href');
                
                // Set a flag in localStorage to indicate we tried the primary link
                localStorage.setItem('triedPrimaryDelete', 'true');
                
                // Continue with the normal link behavior
            });
        }
        
        // Check if we previously tried the primary link and got a 404
        if (localStorage.getItem('triedPrimaryDelete') === 'true') {
            // Show the alternative link
            var altDeleteBtn = document.querySelector('a[href^="DeleteItem.php"]');
            if (altDeleteBtn) {
                altDeleteBtn.style.display = 'inline-block';
                // Clear the flag
                localStorage.removeItem('triedPrimaryDelete');
            }
        }
    });
    </script>
</body>
</html>
