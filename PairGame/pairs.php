<?php
session_start();
$is_registered = isset($_SESSION['username']) && !empty($_SESSION['username']);

// Score submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_score'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false];
    
    // Validate user is logged in
    if (!$is_registered) {
        $response['message'] = 'Not logged in';
        echo json_encode($response);
        exit;
    }
    
    $leaderboardFile = 'leaderboard_data.json';
    
    // Read existing leaderboard data
    $leaderboardData = file_exists($leaderboardFile) 
        ? json_decode(file_get_contents($leaderboardFile), true) 
        : [];
    
    // Prepare score entry
    $difficulty = $_POST['difficulty'];
    $levelsCompleted = isset($_POST['levels_completed']) ? intval($_POST['levels_completed']) : 0;
    $totalScore = intval($_POST['score']);
    
    // Prepare score entry
    $scoreEntry = [
        'username' => $_SESSION['username'],
        'score' => $totalScore,
        'timestamp' => time()
    ];
    
    // Add level-specific details for complex mode
    $scoreEntry['levels_completed'] = $levelsCompleted;
    
    // Safely parse level scores
    $levelScores = [];
    if (isset($_POST['level_scores']) && !empty($_POST['level_scores'])) {
        $levelScores = json_decode($_POST['level_scores'], true);
        if (is_array($levelScores)) {
            $scoreEntry['level_scores'] = $levelScores;
        }
    }
    
    // Initialize difficulty category if not exists
    if (!isset($leaderboardData[$difficulty])) {
        $leaderboardData[$difficulty] = [];
    }
    
    // Add new score
    $leaderboardData[$difficulty][] = $scoreEntry;
    
    // Sort scores for this difficulty
    usort($leaderboardData[$difficulty], function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // Keep only top 10 scores
    $leaderboardData[$difficulty] = array_slice($leaderboardData[$difficulty], 0, count($leaderboardData[$difficulty]));
    
    // Save updated leaderboard
    file_put_contents($leaderboardFile, json_encode($leaderboardData, JSON_PRETTY_PRINT));
    
    $response['success'] = true;
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pairs Game - Play</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="pairs.css">
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
            <div class="game-container">
                <h1>Memory Pairs Game</h1>
                
                <div class="game-controls">
                    <button id="start-game" class="start-game-button">Start the Game</button>
                    <div id="game-info" style="display: none;">
                        <div class="game-stats">
                            <div id="level-display">Level: <span id="current-level">1</span></div>
                            <div id="attempts-display">Attempts: <span id="attempts-count">0</span>/<span id="max-attempts">0</span></div>
                            <div id="time-display">Time: <span id="time-count">0</span>s</div>
                            <div id="score-display">Score: <span id="score-count">0</span></div>
                        </div>
                    </div>
                </div>
                
                <div id="game-board"></div>
                
                <div id="game-over" style="display: none;">
                    <h2>Game Over!</h2>
                    <div id="final-score"></div>
                    <?php if($is_registered): ?>
                        <div class="game-over-buttons">
                            <button id="submit-score" class="game-button">Submit Score</button>
                            <button id="play-again" class="game-button">Play Again</button>
                        </div>
                    <?php else: ?>
                        <div class="register-prompt">
                            <p>Register to save your score on the leaderboard!</p>
                            <a href="registration.php" class="game-button">Register</a>
                            <button id="play-again-unreg" class="game-button">Play Again</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Game configuration - only complex mode
    const gameConfig = {
        complex: {
            startPairs: 3,
            maxPairs: 8,
            maxCardsPerMatch: 4,
            levels: 5,
            maxAttempts: 30
        }
    };
    
    // Game state
    const gameState = {
        difficulty: 'complex', 
        currentLevel: 1,
        attempts: 0,
        maxAttempts: 0,
        timeElapsed: 0,
        score: 0,
        scorePerLevel: {},
        timer: null,
        flippedCards: [],
        matchedPairs: 0,
        totalPairs: 0,
        cardsPerMatch: 2,
        bestScores: {}
    };
    
    // DOM Elements
    const gameControls = document.querySelector('.game-controls');
    const startButton = document.getElementById('start-game');
    const gameBoard = document.getElementById('game-board');
    const gameInfo = document.getElementById('game-info');
    const gameOver = document.getElementById('game-over');
    const levelDisplay = document.getElementById('current-level');
    const attemptsDisplay = document.getElementById('attempts-count');
    const maxAttemptsDisplay = document.getElementById('max-attempts');
    const timeDisplay = document.getElementById('time-count');
    const scoreDisplay = document.getElementById('score-count');
    const finalScore = document.getElementById('final-score');
    const submitScoreButton = document.getElementById('submit-score');
    const playAgainButton = document.getElementById('play-again');
    const playAgainUnregButton = document.getElementById('play-again-unreg');
    
    // Load best scores from localStorage
    function loadBestScores() {
        const storedScores = localStorage.getItem('pairsGameBestScores');
        if (storedScores) {
            gameState.bestScores = JSON.parse(storedScores);
        }
    }
    
    // Save best scores to localStorage
    function saveBestScores() {
        localStorage.setItem('pairsGameBestScores', JSON.stringify(gameState.bestScores));
    }
    
    function generateEmojiSet(numPairs, cardsPerMatch) {
        const skinOptions = ['yellow', 'red', 'green'];
        const eyesOptions = ['normal', 'winking', 'long', 'closed', 'laughing', 'rolling'];
        const mouthOptions = ['smiling', 'open', 'sad', 'straight', 'surprise', 'teeth'];
        
        const emojiSet = [];
        const usedCombinations = new Set();

        // Helper function to generate a unique combination
        function generateUniqueCombination() {
            let combination;
            do {
                combination = {
                    skin: skinOptions[Math.floor(Math.random() * skinOptions.length)],
                    eyes: eyesOptions[Math.floor(Math.random() * eyesOptions.length)],
                    mouth: mouthOptions[Math.floor(Math.random() * mouthOptions.length)]
                };
                
                // Create a unique key for the combination
                const combinationKey = `${combination.skin}-${combination.eyes}-${combination.mouth}`;
                
                // Check if this combination has been used
                if (!usedCombinations.has(combinationKey)) {
                    usedCombinations.add(combinationKey);
                    return combination;
                }
            } while (true);
        }

        // Generate emoji set
        for (let i = 0; i < numPairs; i++) {
            const emoji = generateUniqueCombination();
            
            // Create the matching cards with this emoji combination
            for (let j = 0; j < cardsPerMatch; j++) {
                emojiSet.push({
                    id: `card-${i}-${j}`,
                    pairId: i,
                    emojiCombination: emoji,
                    skinPath: `emoji_assets/skin/${emoji.skin}.png`,
                    eyesPath: `emoji_assets/eyes/${emoji.eyes}.png`,
                    mouthPath: `emoji_assets/mouth/${emoji.mouth}.png`
                });
            }
        }
        
        return shuffleArray(emojiSet);
    }
    
    // Fisher-Yates shuffle algorithm
    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }
    
    // Modify the createGameBoard function to include high score check
    function createGameBoard(level) {
        gameBoard.innerHTML = '';
        gameState.flippedCards = [];
        gameState.matchedPairs = 0;
        gameState.attempts = 0;
        gameState.timeElapsed = 0;
        
        // Determine number of pairs and cards per match based on level
        const numPairs = Math.min(gameConfig.complex.startPairs + level - 1, gameConfig.complex.maxPairs);
        const cardsPerMatch = Math.min(2 + Math.floor((level - 1) / 2), gameConfig.complex.maxCardsPerMatch);
        const maxAttempts = gameConfig.complex.maxAttempts;
        
        document.getElementById('attempts-display').innerHTML = 
            `Attempts: <span id="attempts-count">0</span>/<span id="max-attempts">${maxAttempts}</span>`;
        maxAttemptsDisplay.textContent = maxAttempts;

        gameState.totalPairs = numPairs;
        gameState.cardsPerMatch = cardsPerMatch;
        gameState.maxAttempts = maxAttempts;
        
        // Generate cards
        const cards = generateEmojiSet(numPairs, cardsPerMatch);
        
        // Create card elements
        cards.forEach(card => {
            const cardElement = document.createElement('div');
            cardElement.className = 'card';
            cardElement.dataset.pairId = card.pairId;
            cardElement.dataset.id = card.id;
            
            const cardInner = document.createElement('div');
            cardInner.className = 'card-inner';
            
            const cardFront = document.createElement('div');
            cardFront.className = 'card-front';
            
            const cardBack = document.createElement('div');
            cardBack.className = 'card-back';
            
            // Emoji generation code
            const skinLayer = document.createElement('img');
            skinLayer.src = card.skinPath;
            skinLayer.alt = `${card.emojiCombination.skin} skin`;
            skinLayer.className = 'emoji-layer skin-layer';
            
            const eyesLayer = document.createElement('img');
            eyesLayer.src = card.eyesPath;
            eyesLayer.alt = `${card.emojiCombination.eyes} eyes`;
            eyesLayer.className = 'emoji-layer eyes-layer';
            
            const mouthLayer = document.createElement('img');
            mouthLayer.src = card.mouthPath;
            mouthLayer.alt = `${card.emojiCombination.mouth} mouth`;
            mouthLayer.className = 'emoji-layer mouth-layer';
            
            cardBack.appendChild(skinLayer);
            cardBack.appendChild(eyesLayer);
            cardBack.appendChild(mouthLayer);
            
            cardInner.appendChild(cardFront);
            cardInner.appendChild(cardBack);
            cardElement.appendChild(cardInner);
            
            cardElement.addEventListener('click', () => flipCard(cardElement));
            
            gameBoard.appendChild(cardElement);
        });
        
    
        // Calculate grid columns to make a full grid
        gameBoard.style.gridTemplateColumns = `repeat(${numPairs}, 1fr)`;

        // Update displays
        maxAttemptsDisplay.textContent = maxAttempts;
        attemptsDisplay.textContent = 0;
        timeDisplay.textContent = gameState.timeElapsed;
        
        // Check for high score background
        // Default background first
        document.querySelector('.content-container').style.backgroundColor = 'rgba(128, 128, 128, 0.8)';
    }
    
    // Load leaderboard data and best scores from localStorage
    function loadLeaderboardData() {
        return fetch('leaderboard_data.json')
            .then(response => response.json())
            .catch(error => {
                console.error('Error loading leaderboard data:', error);
                return { complex: [] };
            });
    }

    // Update function to calculate high scores
    function updateHighScoreBackground(level) {
        loadLeaderboardData().then(leaderboardData => {
            // Get current level score
            const currentLevelScore = calculateLevelScore();
            
            // Find highest score for this level in the leaderboard
            let highestScore = 0;
            
            if (leaderboardData.complex && leaderboardData.complex.length > 0) {
                // Look through all entries to find highest score for this level
                leaderboardData.complex.forEach(entry => {
                    if (entry.level_scores && entry.level_scores[level]) {
                        highestScore = Math.max(highestScore, entry.level_scores[level]);
                    }
                });
            }
            
            // Check if current score is higher than the record
            if (currentLevelScore > highestScore) {
                document.querySelector('.content-container').style.backgroundColor = '#FFD700'; // Gold background
            } else {
                document.querySelector('.content-container').style.backgroundColor = 'rgba(128, 128, 128, 0.8)'; // Default background
            }
        });
    }


    // Modify the flipCard function to check max attempts before allowing another flip
    function flipCard(card) {
        // Check if max attempts reached BEFORE allowing another card flip
        if (gameState.attempts >= gameState.maxAttempts) {
            endGame();
            return;
        }
        
        // Ignore if card is already flipped or matched
        if (card.classList.contains('flipped') || card.classList.contains('matched')) {
            return;
        }
        
        // Ignore if we already have enough cards flipped
        if (gameState.flippedCards.length >= gameState.cardsPerMatch) {
            return;
        }
        
        // Flip the card
        card.classList.add('flipped');
        gameState.flippedCards.push(card);
        
        // Check for match if we have flipped enough cards
        if (gameState.flippedCards.length === gameState.cardsPerMatch) {
            gameState.attempts++;
            
            // Update attempts display
            document.getElementById('attempts-count').textContent = gameState.attempts;
            document.getElementById('max-attempts').textContent = gameState.maxAttempts;
            
            // Check if we've reached max attempts AFTER incrementing attempts
            if (gameState.attempts >= gameState.maxAttempts) {
                // If we've reached max attempts, process the current match attempt
                // before ending the game (gives player feedback on their last move)
                
                // Check if all flipped cards have the same pairId
                const pairId = gameState.flippedCards[0].dataset.pairId;
                const allMatch = gameState.flippedCards.every(card => card.dataset.pairId === pairId);
                
                if (allMatch) {
                    // Match found
                    gameState.flippedCards.forEach(card => {
                        card.classList.add('matched');
                    });
                    gameState.flippedCards = [];
                    gameState.matchedPairs++;
                    
                    // Update score
                    updateScore();
                    
                    // Check if level is complete
                    if (gameState.matchedPairs === gameState.totalPairs) {
                        // Save score for this level
                        gameState.scorePerLevel[gameState.currentLevel] = calculateLevelScore();
                        
                        if (gameState.currentLevel < gameConfig.complex.levels) {
                            // Advance to next level
                            gameState.currentLevel++;
                            levelDisplay.textContent = gameState.currentLevel;
                            setTimeout(() => {
                                createGameBoard(gameState.currentLevel);
                            }, 1000);
                            return; // Don't end game yet if we're advancing to next level
                        }
                    }
                } else {
                    // No match, flip cards back after a short delay so player can see the result
                    setTimeout(() => {
                        gameState.flippedCards.forEach(card => {
                            card.classList.remove('flipped');
                        });
                        gameState.flippedCards = [];
                    }, 1000);
                }
                
                // End game after a short delay to show the last move result
                setTimeout(() => {
                    endGame();
                }, 1200);
                
                return;
            }
                
            // Check if all flipped cards have the same pairId
            const pairId = gameState.flippedCards[0].dataset.pairId;
            const allMatch = gameState.flippedCards.every(card => card.dataset.pairId === pairId);
            
            if (allMatch) {
                // Match found
                gameState.flippedCards.forEach(card => {
                    card.classList.add('matched');
                });
                gameState.flippedCards = [];
                gameState.matchedPairs++;
                
                // Update score and check for high score
                updateScore();
                
                // Check if level is complete
                if (gameState.matchedPairs === gameState.totalPairs) {
                    // Save score for this level
                    gameState.scorePerLevel[gameState.currentLevel] = calculateLevelScore();
                    
                    if (gameState.currentLevel < gameConfig.complex.levels) {
                        // Advance to next level
                        gameState.currentLevel++;
                        levelDisplay.textContent = gameState.currentLevel;
                        setTimeout(() => {
                            createGameBoard(gameState.currentLevel);
                        }, 1000);
                    } else {
                        // Game complete
                        endGame();
                    }
                }
            } else {
                // No match, flip cards back after delay
                setTimeout(() => {
                    gameState.flippedCards.forEach(card => {
                        card.classList.remove('flipped');
                    });
                    gameState.flippedCards = [];
                }, 1000);
            }
        }
    }

    // Calculate score for current level

    function calculateLevelScore() {
        // Base score of 200
        let baseScore = 200;
        
        // Add 20 points for each correct match
        baseScore += gameState.matchedPairs * 20;
        
        // Subtract 2 points for each incorrect guess
        // Number of incorrect attempts = total attempts - matched pairs
        const incorrectAttempts = Math.max(0, gameState.attempts - gameState.matchedPairs);
        baseScore -= incorrectAttempts * 2;
        
        // Subtract 1 point for each second elapsed
        baseScore -= gameState.timeElapsed;
        
        // Ensure score doesn't go below 0
        return Math.max(baseScore, 0);
    }

    // Update overall score
    function updateScore() {
        // Calculate current level score
        const currentScore = calculateLevelScore();
        
        // Sum all completed level scores plus current level score
        gameState.score = Object.values(gameState.scorePerLevel).reduce((total, score) => total + score, currentScore);
        scoreDisplay.textContent = gameState.score;
        
        // Check if current level score is a high score
        updateHighScoreBackground(gameState.currentLevel);
    }
    
    function startTimer() {
        // Clear any existing timer before starting new one
        clearInterval(gameState.timer);
        gameState.timer = setInterval(() => {
            gameState.timeElapsed++;
            timeDisplay.textContent = gameState.timeElapsed;
            updateScore();
        }, 1000);
    }
    
    // End game
    function endGame() {
        clearInterval(gameState.timer);

        // Give completion bonus if all levels completed successfully
        if (gameState.currentLevel >= gameConfig.complex.levels && gameState.matchedPairs === gameState.totalPairs) {
            const completionBonus = 200;
            gameState.score += completionBonus;
        }

        // Save best scores
        for (const level in gameState.scorePerLevel) {
            const levelKey = `level-${level}`;
            if (!gameState.bestScores[levelKey] || gameState.scorePerLevel[level] > gameState.bestScores[levelKey]) {
                gameState.bestScores[levelKey] = gameState.scorePerLevel[level];
            }
        }

        const totalKey = 'total';
        if (!gameState.bestScores[totalKey] || gameState.score > gameState.bestScores[totalKey]) {
            gameState.bestScores[totalKey] = gameState.score;
        }

        saveBestScores();

        // Display final score with detailed breakdown
        finalScore.innerHTML = `
            <p>Your final score: ${gameState.score}</p>
            <p>Time: ${gameState.timeElapsed}s</p>
            <p>Attempts: ${gameState.attempts}</p>
            <p>Levels Completed: ${gameState.currentLevel}</p>
        
        `;

        // Show game over screen
        gameOver.style.display = 'block';
    }
            
    // Reset game state
    function resetGame() {
        // Clear any existing timer
        clearInterval(gameState.timer);
        document.querySelector('.content-container').style.backgroundColor = 'rgba(128, 128, 128, 0.8)'; 
        // Reset game state
        gameState.currentLevel = 1;
        gameState.attempts = 0;
        gameState.timeElapsed = 0;
        gameState.score = 0;
        gameState.scorePerLevel = {};
        gameState.flippedCards = [];
        gameState.matchedPairs = 0;
        
        // Reset displays
        levelDisplay.textContent = gameState.currentLevel;
        attemptsDisplay.textContent = gameState.attempts;
        timeDisplay.textContent = gameState.timeElapsed;
        scoreDisplay.textContent = gameState.score;
        
        // Hide game over screen
        gameOver.style.display = 'none';
        
        // Create new game board
        createGameBoard(gameState.currentLevel);
    }
    
    // Start new game
    function startGame() {
        // Reset game state
        resetGame();
        
        // Show game info
        gameInfo.style.display = 'block';
        
        // Start timer
        startTimer();
        
        // Hide start button
        startButton.style.display = 'none';
    }
    
    // Submit score to server
    function submitScore() {
        const formData = new FormData();
        formData.append('submit_score', 'true');
        formData.append('score', gameState.score);
        formData.append('difficulty', 'complex');
        
        // Subtract 1 from current level as game ends before reaching next level
        formData.append('levels_completed', gameState.currentLevel - 1);
        
        // Convert level scores to JSON to send to server
        const levelScores = {};
        for (const level in gameState.scorePerLevel) {
            levelScores[level] = gameState.scorePerLevel[level];
        }
        
        // Only append level_scores if there are actually scores
        if (Object.keys(levelScores).length > 0) {
            formData.append('level_scores', JSON.stringify(levelScores));
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('Score submitted successfully!');
                    window.location.href = 'leaderboard.php';
                } else {
                    alert('Error submitting score.');
                }
            } catch (error) {
                console.error('Unexpected response:', text);
                alert('An unexpected error occurred while submitting the score.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error submitting score. Please try again.');
        });
    }
    
    function resetToStart() {
        // Clear game state and UI elements
        clearInterval(gameState.timer);
        gameOver.style.display = 'none';
        gameInfo.style.display = 'none';
        
        // Reset all game state values
        gameState.currentLevel = 1;
        gameState.attempts = 0;
        gameState.timeElapsed = 0;
        gameState.score = 0;
        gameState.scorePerLevel = {};

        // Reset background color to default
        document.querySelector('.content-container').style.backgroundColor = 'rgba(128, 128, 128, 0.8)';
        // Update displays
        levelDisplay.textContent = '1';
        attemptsDisplay.textContent = '0';
        timeDisplay.textContent = '0';
        scoreDisplay.textContent = '0';
        
        // Clear game board
        gameBoard.innerHTML = '';
        
        // Show start button again
        startButton.style.display = 'block';
    }
    
    // Event listeners
    startButton.addEventListener('click', startGame);
    
    if (submitScoreButton) {
        submitScoreButton.addEventListener('click', submitScore);
    }
    
    if (playAgainButton) {
        playAgainButton.addEventListener('click', resetToStart);
    }
    
    if (playAgainUnregButton) {
        playAgainUnregButton.addEventListener('click', resetToStart);
    }
    
    // Load best scores when page loads
    loadBestScores();
    </script>
</body>
</html>