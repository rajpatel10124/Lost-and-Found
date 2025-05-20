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

// Get all lost items
$lostItems = [];
$sql = "SELECT * FROM lost_items WHERE status = 'approved'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lostItems[] = $row;
    }
}

// Get all found items
$foundItems = [];
$sql = "SELECT * FROM found_items WHERE status = 'approved'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $foundItems[] = $row;
    }
}

// Find potential matches
$matches = [];
foreach ($lostItems as $lostItem) {
    foreach ($foundItems as $foundItem) {
        // Calculate match score based on item name and description
        $score = calculateMatchScore($lostItem, $foundItem);
        
        // Only include matches with a score above threshold
        if ($score > 0.3) {
            $matches[] = [
                'lost_item' => $lostItem,
                'found_item' => $foundItem,
                'score' => $score
            ];
        }
    }
}

// Sort matches by score (highest first)
usort($matches, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

// Function to calculate match score between lost and found items
function calculateMatchScore($lostItem, $foundItem) {
    // Convert to lowercase for case-insensitive comparison
    $lostName = strtolower($lostItem['item_name']);
    $lostDesc = strtolower($lostItem['description']);
    $foundName = strtolower($foundItem['item_name']);
    $foundDesc = strtolower($foundItem['description']);
    $lostLocation = strtolower($lostItem['location']);
    $foundLocation = strtolower($foundItem['location']);
    
    // Weight factors for different match components
    $nameWeight = 0.5;
    $descWeight = 0.3;
    $locationWeight = 0.2;
    
    // Name similarity (exact match gets full score, partial match gets partial score)
    $nameSimilarity = 0;
    if ($lostName === $foundName) {
        $nameSimilarity = 1.0;
    } else {
        // Check for partial name match
        $nameWords = explode(' ', $lostName);
        $foundNameWords = explode(' ', $foundName);
        $matchingWords = array_intersect($nameWords, $foundNameWords);
        if (count($nameWords) > 0) {
            $nameSimilarity = count($matchingWords) / count($nameWords);
        }
    }
    
    // Description similarity
    $descSimilarity = 0;
    // Extract keywords (words with 3+ characters)
    $lostWords = preg_split('/\W+/', $lostDesc);
    $foundWords = preg_split('/\W+/', $foundDesc);
    
    // Filter out short words
    $lostWords = array_filter($lostWords, function($word) {
        return strlen($word) >= 3;
    });
    
    // Count matching words
    $matchCount = 0;
    $totalWords = count($lostWords);
    
    if ($totalWords > 0) {
        foreach ($lostWords as $word) {
            if (strpos($foundDesc, $word) !== false) {
                $matchCount++;
            }
        }
        $descSimilarity = $matchCount / $totalWords;
    }
    
    // Location similarity (0-1 scale)
    $locationSimilarity = 0;
    if ($lostLocation === $foundLocation) {
        $locationSimilarity = 1.0;
    } else {
        // Check for partial location match
        similar_text($lostLocation, $foundLocation, $percent);
        $locationSimilarity = $percent / 100;
    }
    
    // Calculate weighted score
    $finalScore = ($nameSimilarity * $nameWeight) + 
                 ($descSimilarity * $descWeight) + 
                 ($locationSimilarity * $locationWeight);
    
    // Cap at 1.0 (100%)
    return min(1, $finalScore);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potential Matches - Lost and Found Portal</title>
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
        h1, h2 {
            color: #333;
        }
        .match-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
        }
        .match-score {
            flex: 1;
            min-width: 150px;
            text-align: center;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .score-circle {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #f1f1f1;
            margin: 0 auto 10px;
        }
        
        .score-circle span {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: bold;
        }
        .match-details {
            flex: 3;
            min-width: 300px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .item-box {
            flex: 1;
            min-width: 250px;
            padding: 15px;
            border-radius: 5px;
        }
        .lost-item {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
        }
        .found-item {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
        }
        .item-box h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .item-info {
            margin-bottom: 5px;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .no-matches {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 50px;
            text-align: center;
        }
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
        }
        .match-note {
            font-size: 0.8rem;
            font-style: italic;
            color: #666;
            margin-top: 5px;
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
        <h1>Potential Matches</h1>
        <p>Our system has found the following potential matches between lost and found items.</p>
        
        <?php if (empty($matches)): ?>
            <div class="no-matches">
                <h2>No Potential Matches Found</h2>
                <p>There are currently no potential matches between lost and found items.</p>
                <p>Please check back later or report more items to improve matching chances.</p>
            </div>
        <?php else: ?>
            <?php foreach ($matches as $match): ?>
                <div class="match-card">
                    <div class="match-score">
                        <div class="score-circle" style="background: conic-gradient(#4CAF50 <?php echo round($match['score'] * 360); ?>deg, #f1f1f1 0deg);">
                            <span><?php echo round($match['score'] * 100) . '%'; ?></span>
                        </div>
                        <p>Match Score</p>
                        <?php if ($match['score'] < 0.5): ?>
                            <p class="match-note">(Low confidence match)</p>
                        <?php elseif ($match['score'] >= 0.9): ?>
                            <p class="match-note">(High confidence match)</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="match-details">
                        <div class="item-box lost-item">
                            <h3>Lost Item</h3>
                            <p class="item-info"><strong>Item:</strong> <?php echo htmlspecialchars($match['lost_item']['item_name']); ?></p>
                            <p class="item-info"><strong>Description:</strong> <?php echo htmlspecialchars(substr($match['lost_item']['description'], 0, 100)) . (strlen($match['lost_item']['description']) > 100 ? '...' : ''); ?></p>
                            <p class="item-info"><strong>Date Lost:</strong> <?php echo date('M d, Y', strtotime($match['lost_item']['date_lost'])); ?></p>
                            <p class="item-info"><strong>Location:</strong> <?php echo htmlspecialchars($match['lost_item']['location']); ?></p>
                            <a href="item-details.php?type=lost&id=<?php echo $match['lost_item']['id']; ?>" class="btn">View Details</a>
                        </div>
                        
                        <div class="item-box found-item">
                            <h3>Found Item</h3>
                            <p class="item-info"><strong>Item:</strong> <?php echo htmlspecialchars($match['found_item']['item_name']); ?></p>
                            <p class="item-info"><strong>Description:</strong> <?php echo htmlspecialchars(substr($match['found_item']['description'], 0, 100)) . (strlen($match['found_item']['description']) > 100 ? '...' : ''); ?></p>
                            <p class="item-info"><strong>Date Found:</strong> <?php echo date('M d, Y', strtotime($match['found_item']['date_found'])); ?></p>
                            <p class="item-info"><strong>Location:</strong> <?php echo htmlspecialchars($match['found_item']['location']); ?></p>
                            <a href="item-details.php?type=found&id=<?php echo $match['found_item']['id']; ?>" class="btn">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Lost and Found Portal. All rights reserved.</p>
    </footer>
</body>
</html>
