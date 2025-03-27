<?php
session_start();
$is_registered = isset($_SESSION['username']) && !empty($_SESSION['username']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pairs Game - Home</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="index.css">
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
            <?php if($is_registered): ?>
                <div class="welcome-message">
                    <h1>Welcome to Pairs</h1>
                    <p>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
                    <button class="play-button">
                        <a href="pairs.php">Click here to play</a>
                    </button>
                </div>
            <?php else: ?>
                <div class="welcome-message">
                    <h1>Welcome to Pairs</h1>
                    <p>You're not using a registered session? <a href="registration.php">Register now</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>