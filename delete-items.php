<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Simple configuration - hardcoded to ensure it works
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'lost_and_found';

// Get item type and ID from URL
$type = isset($_GET['type']) && $_GET['type'] === 'found' ? 'found' : 'lost';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if ($id <= 0) {
    // Invalid ID, redirect to items list
    header("Location: view-items.php?type=$type");
    exit;
}

$table = $type . '_items';
$error = '';
$success = false;

// Create direct database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the item exists
$sql = "SELECT * FROM $table WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Item not found, redirect to items list
    $stmt->close();
    $conn->close();
    header("Location: view-items.php?type=$type");
    exit;
}

$item = $result->fetch_assoc();
$stmt->close();

// Check if the user is the owner of the item or admin
$isOwner = ($item['contact_email'] === $_SESSION['username']);
$isAdmin = ($_SESSION['username'] === 'admin');

if (!$isOwner && !$isAdmin) {
    $error = "You can only delete items that you have reported.";
}

// Process deletion if confirmed and no errors
if ($confirm && empty($error)) {
    // Delete the image file if it exists
    if (!empty($item['image']) && file_exists('uploads/' . $item['image'])) {
        unlink('uploads/' . $item['image']);
    }
    
    // Delete the item from the database
    $sql = "DELETE FROM $table WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $success = true;
        
        // Also delete any matches associated with this item
        if ($type === 'lost') {
            $sql = "DELETE FROM matches WHERE lost_id = ?";
        } else {
            $sql = "DELETE FROM matches WHERE found_id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $error = "Failed to delete the item. Please try again. Error: " . $conn->error;
    }
}

$conn->close();

// If deletion was successful, redirect to the items list
if ($success) {
    header("Location: view-items.php?type=$type&deleted=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Item - Lost and Found Portal</title>
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
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        .item-details {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid #4CAF50;
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
        <h1>Delete <?php echo ucfirst($type); ?> Item</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <p>
                <a href="item-details.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>" class="btn btn-primary">Back to Item Details</a>
            </p>
        <?php elseif (!$confirm): ?>
            <div class="item-details">
                <p><strong>Item:</strong> <?php echo htmlspecialchars($item['item_name']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                <p><strong>Date <?php echo ucfirst($type); ?>:</strong> <?php echo date('M d, Y', strtotime($item['date_' . $type])); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
            </div>
            
            <p>Are you sure you want to delete this <?php echo $type; ?> item? This action cannot be undone.</p>
            
            <p>
                <a href="delete-item.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>&confirm=yes" class="btn btn-danger">Yes, Delete Item</a>
                <a href="item-details.php?type=<?php echo $type; ?>&id=<?php echo $id; ?>" class="btn btn-primary">No, Cancel</a>
            </p>
            
            <!-- Debug information -->
            <div class="debug-info">
                <p>Debug Info:</p>
                <p>User: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p>Item Owner: <?php echo htmlspecialchars($item['contact_email']); ?></p>
                <p>Is Owner: <?php echo $isOwner ? 'Yes' : 'No'; ?></p>
                <p>Is Admin: <?php echo $isAdmin ? 'Yes' : 'No'; ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Lost and Found Portal. All rights reserved.</p>
    </footer>
</body>
</html>
