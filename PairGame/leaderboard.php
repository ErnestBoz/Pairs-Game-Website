<?php
session_start();
$is_registered = isset($_SESSION['username']) && !empty($_SESSION['username']);

// If not registered, redirect to registration
if (!$is_registered) {
    header("Location: registration.php");
    exit;
}

// Initialize leaderboard data structure
if (!file_exists('leaderboard_data.json')) {
    file_put_contents('leaderboard_data.json', json_encode(['complex' => []]));
}

// Handle score submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['score'])) {
    $leaderboardData = json_decode(file_get_contents('leaderboard_data.json'), true);
    
    $username = $_SESSION['username'];
    $score = intval($_POST['score']);
    $timestamp = time();
    $levels_completed = isset($_POST['levels_completed']) ? intval($_POST['levels_completed']) : 0;
    
    // Add level scores if they exist
    $levelScores = [];
    if (isset($_POST['level_scores']) && is_array($_POST['level_scores'])) {
        $levelScores = $_POST['level_scores'];
    }
    
    // Add new entry
    $entry = [
        'username' => $username,
        'score' => $score,
        'timestamp' => $timestamp,
        'levels_completed' => $levels_completed,
        'level_scores' => $levelScores
    ];
    
    $leaderboardData['complex'][] = $entry;
    
    // Sort by score (highest first)
    usort($leaderboardData['complex'], function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // Save updated leaderboard
    file_put_contents('leaderboard_data.json', json_encode($leaderboardData));
    
    // Redirect to avoid form resubmission
    header("Location: leaderboard.php?submitted=1");
    exit;
}

// Get leaderboard data
$leaderboardData = json_decode(file_get_contents('leaderboard_data.json'), true);
$scoreData = isset($leaderboardData['complex']) ? $leaderboardData['complex'] : [];

// Define the consistent date format to use across PHP and JavaScript
$dateFormat = 'Y-m-d H:i';
?>

<!DOCTYPE html>
<html lang="en">
<script>
  console.log('PHP Data (scoreData):', <?php echo json_encode($scoreData); ?>);
</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pairs Game - Leaderboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="leaderboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <a href="index.php" name="home">Home</a>
        </div>
        <div class="navbar-right">
            <a href="pairs.php" name="memory">Play Pairs</a>
            
            <?php if(isset($_SESSION['username']) && !empty($_SESSION['username'])): ?>
                <a href="leaderboard.php" name="leaderboard">Leaderboard</a>
                <?php if(isset($_SESSION['emoji_type']) && $_SESSION['emoji_type'] == 'custom'): ?>
                    <span class="user-emoji custom-emoji">
                        <img src="emoji_assets/skin/<?php echo $_SESSION['emoji_skin']; ?>.png" class="emoji-layer skin-layer">
                        <img src="emoji_assets/eyes/<?php echo $_SESSION['emoji_eyes']; ?>.png" class="emoji-layer eyes-layer">
                        <img src="emoji_assets/mouth/<?php echo $_SESSION['emoji_mouth']; ?>.png" class="emoji-layer mouth-layer">
                    </span>
                <?php endif; ?>
              
            <?php else: ?>
                <a href="registration.php" name="register">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <div id="main">
        <div class="content-container">
            <div class="leaderboard-container">
                <h1>Leaderboard</h1>
                
                <?php if(isset($_GET['submitted']) && $_GET['submitted'] == 1): ?>
                    <div class="success-message">Your score has been submitted successfully!</div>
                <?php endif; ?>
                
                <div class="leaderboard-tabs">
                    <button class="tab-button active" onclick="switchTab('total')">Total Scores</button>
                    <button class="tab-button" onclick="switchTab('levels')">Level Scores</button>
                </div>
                
                <div id="total-scores" class="leaderboard-tab leaderboard-box">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Player</th>
                                <th>Score</th>
                                <th>Levels Completed</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($scoreData)): ?>
                                <tr>
                                    <td colspan="5">No scores yet. Be the first to play!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($scoreData as $index => $entry): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($entry['username']); ?></td>
                                        <td><?php echo $entry['score']; ?></td>
                                        <td><?php echo isset($entry['levels_completed']) ? $entry['levels_completed'] : 'N/A'; ?></td>
                                        <td><?php echo date($dateFormat, $entry['timestamp']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="level-scores" class="leaderboard-tab leaderboard-box" style="display: none;">
                    <div class="level-selector">
                        <label for="level-select">Select Level:</label>
                        <select id="level-select" onchange="showLevelScores()">
                            <option value="1">Level 1</option>
                            <option value="2">Level 2</option>
                            <option value="3">Level 3</option>
                            <option value="4">Level 4</option>
                            <option value="5">Level 5</option>
                        </select>
                    </div>
                    
                    <table class="leaderboard-table" id="level-scores-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Player</th>
                                <th>Level Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="level-scores-body">
                            <tr>
                                <td colspan="4">Select a level to view scores</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Leaderboard data from PHP
    const leaderboardData = <?php echo json_encode($scoreData); ?>;
    // Use the same date format as PHP
    const dateFormat = '<?php echo $dateFormat; ?>';

    // Format date using the same format as PHP
    function formatDate(timestamp) {
        const date = new Date(timestamp * 1000);
        
        // Format to match PHP's Y-m-d H:i format
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}`;
    }

    // Switch between tabs
    function switchTab(tab) {
        console.log("switchTab called for tab:", tab); // Debug log

        // Hide all tabs
        const totalScoresElement = document.getElementById('total-scores');
        const levelScoresElement = document.getElementById('level-scores');

        if (totalScoresElement) {
            totalScoresElement.style.display = 'none';
        } else {
            console.warn("Element with ID 'total-scores' not found when trying to hide.");
        }

        if (levelScoresElement) {
            levelScoresElement.style.display = 'none';
        } else {
            console.warn("Element with ID 'level-scores' not found when trying to hide.");
        }

        // Show selected tab 
        let selectedTabId;
        if (tab === 'levels') {
            selectedTabId = 'level-scores'; // Correct ID for Level Scores
        } else {
            selectedTabId = 'total-scores'; // ID for Total Scores
        }
        const selectedTabElement = document.getElementById(selectedTabId);


        if (selectedTabElement) {
            selectedTabElement.style.display = 'block';
        } else {
            console.error(`Element with ID '${selectedTabId}' not found when trying to show.`);
        }


        // Update active button
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        event.currentTarget.classList.add('active');

        // If showing level scores, refresh the display
        if (tab === 'levels') {
            showLevelScores();
        }
    }

    // Show scores for selected level
    function showLevelScores() {
        const selectedLevel = document.getElementById('level-select').value;
        const levelScoresBody = document.getElementById('level-scores-body');

        // Filter entries with level scores for the selected level
        const levelEntries = leaderboardData.filter(entry => {
            return entry.level_scores && entry.level_scores[String(selectedLevel)] !== undefined; //Explicit String conversion as precaution
        });

        // Sort by level score (highest first)
        levelEntries.sort((a, b) => {
            return b.level_scores[selectedLevel] - a.level_scores[selectedLevel];
        });

        // Generate table rows
        if (levelEntries.length === 0) {
            levelScoresBody.innerHTML = `
                <tr>
                    <td colspan="4">No scores for Level ${selectedLevel} yet</td>
                </tr>
            `;
        } else {
            levelScoresBody.innerHTML = levelEntries.map((entry, index) => {
                const score = entry.level_scores[selectedLevel];
                const formattedDate = formatDate(entry.timestamp);

                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${entry.username}</td>
                        <td>${score}</td>
                        <td>${formattedDate}</td>
                    </tr>
                `;
            }).join('');
        }
    }

    // Initialize the level scores display when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // If the level scores tab is active, show the scores
        if (document.querySelector('.tab-button:nth-child(2)').classList.contains('active')) {
            showLevelScores();
        }
    });
</script>
    
</body>
</html>