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

// Check if lost_items table exists
$tableExists = false;
$result = $conn->query("SHOW TABLES LIKE 'lost_items'");
if ($result->num_rows > 0) {
    $tableExists = true;
}

// Define all required columns and their definitions
$requiredColumns = [
    'id' => 'INT(11) AUTO_INCREMENT PRIMARY KEY',
    'item_name' => 'VARCHAR(255) NOT NULL',
    'description' => 'TEXT NOT NULL',
    'date_lost' => 'DATE NOT NULL',
    'location' => 'VARCHAR(255) NOT NULL',
    'image' => 'VARCHAR(255)',
    'contact_name' => 'VARCHAR(255) NOT NULL',
    'contact_email' => 'VARCHAR(255) NOT NULL',
    'contact_phone' => 'VARCHAR(50)',
    'status' => "ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'",
    'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
];

// If table doesn't exist, create it with all required columns
if (!$tableExists) {
    $columnDefinitions = [];
    foreach ($requiredColumns as $column => $definition) {
        $columnDefinitions[] = "$column $definition";
    }
    
    $sql = "CREATE TABLE lost_items (" . implode(', ', $columnDefinitions) . ")";
    
    if ($conn->query($sql) !== TRUE) {
        echo "<div class='alert alert-error'>Error creating table: " . $conn->error . "</div>";
    }
} else {
    // Table exists, check if all required columns exist
    $result = $conn->query("DESCRIBE lost_items");
    $existingColumns = [];
    
    while ($row = $result->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
    }
    
    // Add any missing columns
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            $sql = "ALTER TABLE lost_items ADD COLUMN $column $definition";
            
            // Special handling for primary key
            if ($column === 'id') {
                $sql = "ALTER TABLE lost_items ADD COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
            }
            
            if ($conn->query($sql) !== TRUE) {
                echo "<div class='alert alert-error'>Error adding column $column: " . $conn->error . "</div>";
            }
        }
    }
}

// Create uploads directory if it doesn't exist
$uploads_dir = 'uploads';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $item_name = $_POST['item_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_lost = $_POST['date_lost'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact_name = $_POST['contact_name'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    
    // Validation
    if (empty($item_name)) {
        $errors[] = "Item name is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($date_lost)) {
        $errors[] = "Date lost is required";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    if (empty($contact_name)) {
        $errors[] = "Contact name is required";
    }
    
    if (empty($contact_email)) {
        $errors[] = "Contact email is required";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Handle image upload
    $image_name = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed";
        } else {
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_path = 'uploads/' . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $errors[] = "Failed to upload image";
                $image_name = null;
            }
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            // Check if all required columns exist in the table
            $result = $conn->query("DESCRIBE lost_items");
            $existingColumns = [];
            
            while ($row = $result->fetch_assoc()) {
                $existingColumns[] = $row['Field'];
            }
            
            // Build the SQL query based on existing columns
            $columns = [];
            $values = [];
            $placeholders = [];
            
            if (in_array('item_name', $existingColumns)) {
                $columns[] = 'item_name';
                $values[] = $item_name;
                $placeholders[] = '?';
            }
            
            if (in_array('description', $existingColumns)) {
                $columns[] = 'description';
                $values[] = $description;
                $placeholders[] = '?';
            }
            
            if (in_array('date_lost', $existingColumns)) {
                $columns[] = 'date_lost';
                $values[] = $date_lost;
                $placeholders[] = '?';
            }
            
            if (in_array('location', $existingColumns)) {
                $columns[] = 'location';
                $values[] = $location;
                $placeholders[] = '?';
            }
            
            if (in_array('image', $existingColumns)) {
                $columns[] = 'image';
                $values[] = $image_name;
                $placeholders[] = '?';
            }
            
            if (in_array('contact_name', $existingColumns)) {
                $columns[] = 'contact_name';
                $values[] = $contact_name;
                $placeholders[] = '?';
            }
            
            if (in_array('contact_email', $existingColumns)) {
                $columns[] = 'contact_email';
                $values[] = $contact_email;
                $placeholders[] = '?';
            }
            
            if (in_array('contact_phone', $existingColumns)) {
                $columns[] = 'contact_phone';
                $values[] = $contact_phone;
                $placeholders[] = '?';
            }
            
            // Prepare and execute the SQL query
            $sql = "INSERT INTO lost_items (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Create the type string for bind_param
            $types = str_repeat('s', count($values));
            
            // Bind parameters dynamically
            $bindParams = array($types);
            foreach ($values as $key => $value) {
                $bindParams[] = &$values[$key];
            }
            
            call_user_func_array(array($stmt, 'bind_param'), $bindParams);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - Lost and Found Portal</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
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
        <h1>Report a Lost Item</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p>Your lost item has been reported successfully!</p>
                <p><a href="view-items.php?type=lost">View all lost items</a> or <a href="index.php">return to the homepage</a>.</p>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="report-lost.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" id="item_name" name="item_name" required value="<?php echo isset($_POST['item_name']) ? htmlspecialchars($_POST['item_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="date_lost">Date Lost *</label>
                    <input type="date" id="date_lost" name="date_lost" required value="<?php echo isset($_POST['date_lost']) ? htmlspecialchars($_POST['date_lost']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="location">Location Lost *</label>
                    <input type="text" id="location" name="location" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="image">Image of the Item</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                
                <h3>Contact Information</h3>
                
                <div class="form-group">
                    <label for="contact_name">Your Name *</label>
                    <input type="text" id="contact_name" name="contact_name" required value="<?php echo isset($_POST['contact_name']) ? htmlspecialchars($_POST['contact_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Email *</label>
                    <input type="email" id="contact_email" name="contact_email" required value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_phone">Phone Number</label>
                    <input type="text" id="contact_phone" name="contact_phone" value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Submit Report</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Lost and Found Portal. All rights reserved.</p>
    </footer>
</body>
</html>
