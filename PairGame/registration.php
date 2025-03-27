<?php
session_start();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate username
    $username = $_POST['username'] ?? '';
    $invalid_chars = array('!', '@', '#', '%', '&', '^', '*', '(', ')', '+', '=', '{', '}', '[', ']', '-', ';', ':', '"', "'", '<', '>', '?', '/');
    $has_invalid_chars = false;
    
    foreach ($invalid_chars as $char) {
        if (strpos($username, $char) !== false) {
            $has_invalid_chars = true;
            break;
        }
    }
    
    if (empty($username)) {
        $error_message = 'Username cannot be empty.';
    } elseif ($has_invalid_chars) {
        $error_message = 'Username contains invalid characters. Please avoid using special characters.';
    } else {
        // Store custom emoji parts
        $skin = $_POST['skin'] ?? 'yellow';
        $eyes = $_POST['eyes'] ?? 'normal';
        $mouth = $_POST['mouth'] ?? 'smiling';
        
        $_SESSION['emoji_type'] = 'custom';
        $_SESSION['emoji_skin'] = $skin;
        $_SESSION['emoji_eyes'] = $eyes;
        $_SESSION['emoji_mouth'] = $mouth;
        
        // Set common session variables
        $_SESSION['username'] = $username;
        setcookie('username', $username, time() + (86400 * 30), "/");
        
        $success_message = 'Registration successful! Redirecting...';
        
        header("refresh:0;url=pairs.php");
        
        // Add JavaScript instant redirect
        echo '<script>
            setTimeout(function() {
                window.location.href = "pairs.php";
            }, 1000);
        </script>';
        exit; // Stop execution after registration is successful
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pairs Game - Registration</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="registration.css">
   
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
            <div class="registration-form">
                <h1>Create Your Profile</h1>
                
                <?php if($error_message): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if($success_message): ?>
                    <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="registration.php">
                    <div class="form-group">
                        <label for="username">Username/Nickname:</label>
                        <input type="text" id="username" name="username" required>
                        <small>Invalid characters: ! @ # % & ^ * ( ) + = { } [ ] — ; : " ' < > ? /</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Choose Your Avatar:</label>
                        <input type="hidden" name="emoji_type" value="custom">
                    </div>
                    
                    <div id="custom-emoji-options" class="emoji-options">
                        <div class="emoji-builder-container">
                            <!-- Skin selection -->
                            <div class="builder-section">
                                <label>Skin Color:</label>
                                <div class="builder-options skin-options">
                                    <div class="builder-option selected" data-value="yellow" onclick="selectSkin('yellow')">
                                        <img src="emoji_assets/skin/yellow.png" alt="Yellow skin">
                                    </div>
                                    <div class="builder-option" data-value="red" onclick="selectSkin('red')">
                                        <img src="emoji_assets/skin/red.png" alt="Red skin">
                                    </div>
                                    <div class="builder-option" data-value="green" onclick="selectSkin('green')">
                                        <img src="emoji_assets/skin/green.png" alt="Green skin">
                                    </div>                    
                                </div>
                                <input type="hidden" name="skin" id="skin-input" value="yellow">
                            </div>
                            
                            <!-- Eyes selection -->
                            <div class="builder-section">
                                <label>Eyes:</label>
                                <div class="builder-options eyes-options">
                                    <div class="builder-option selected" data-value="normal" onclick="selectEyes('normal')">
                                        <img src="emoji_assets/eyes/normal.png" alt="Normal eyes">
                                    </div>
                                    <div class="builder-option" data-value="winking" onclick="selectEyes('winking')">
                                        <img src="emoji_assets/eyes/winking.png" alt="Winking eyes">
                                    </div>
                                    <div class="builder-option" data-value="long" onclick="selectEyes('long')">
                                        <img src="emoji_assets/eyes/long.png" alt="Long eyes">
                                    </div>
                                    <div class="builder-option" data-value="closed" onclick="selectEyes('closed')">
                                        <img src="emoji_assets/eyes/closed.png" alt="Closed eyes">
                                    </div>
                                    <div class="builder-option" data-value="laughing" onclick="selectEyes('laughing')">
                                        <img src="emoji_assets/eyes/laughing.png" alt="Laughing eyes">
                                    </div>
                                    <div class="builder-option" data-value="rolling" onclick="selectEyes('rolling')">
                                        <img src="emoji_assets/eyes/rolling.png" alt="Rolling eyes">
                                    </div>
                                </div>
                                <input type="hidden" name="eyes" id="eyes-input" value="normal">
                            </div>
                            
                            <!-- Mouth selection -->
                            <div class="builder-section">
                                <label>Mouth:</label>
                                <div class="builder-options mouth-options">
                                    <div class="builder-option selected" data-value="smiling" onclick="selectMouth('smiling')">
                                        <img src="emoji_assets/mouth/smiling.png" alt="Smiling mouth">
                                    </div>
                                    <div class="builder-option" data-value="open" onclick="selectMouth('open')">
                                        <img src="emoji_assets/mouth/open.png" alt="Open mouth">
                                    </div>
                                    <div class="builder-option" data-value="sad" onclick="selectMouth('sad')">
                                        <img src="emoji_assets/mouth/sad.png" alt="Sad mouth">
                                    </div>
                                    <div class="builder-option" data-value="straight" onclick="selectMouth('straight')">
                                        <img src="emoji_assets/mouth/straight.png" alt="Straight mouth">
                                    </div>
                                    <div class="builder-option" data-value="surprise" onclick="selectMouth('surprise')">
                                        <img src="emoji_assets/mouth/surprise.png" alt="Surprise mouth">
                                    </div>
                                    <div class="builder-option" data-value="teeth" onclick="selectMouth('teeth')">
                                        <img src="emoji_assets/mouth/teeth.png" alt="Teeth mouth">
                                    </div>
                                </div>
                                <input type="hidden" name="mouth" id="mouth-input" value="smiling">
                            </div>
                            
                            <!-- Preview -->
                            <div class="custom-emoji-preview">
                                <img src="emoji_assets/skin/yellow.png" class="custom-emoji-layer" id="skin-preview">
                                <img src="emoji_assets/eyes/normal.png" class="custom-emoji-layer" id="eyes-preview">
                                <img src="emoji_assets/mouth/smiling.png" class="custom-emoji-layer" id="mouth-preview">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-button">Register</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function selectOption(container, optionValue) {
        // Remove selected class from all options in the container
        const options = document.querySelectorAll(container + ' .builder-option');
        options.forEach(option => {
            option.classList.remove('selected');
        });
        
        // Add selected class to the chosen option
        const selectedOption = document.querySelector(container + ' .builder-option[data-value="' + optionValue + '"]');
        if (selectedOption) {
            selectedOption.classList.add('selected');
        }
    }
    
    function selectSkin(skin) {
        selectOption('.skin-options', skin);
        document.getElementById('skin-input').value = skin;
        document.getElementById('skin-preview').src = 'emoji_assets/skin/' + skin + '.png';
    }
    
    function selectEyes(eyes) {
        selectOption('.eyes-options', eyes);
        document.getElementById('eyes-input').value = eyes;
        document.getElementById('eyes-preview').src = 'emoji_assets/eyes/' + eyes + '.png';
    }
    
    function selectMouth(mouth) {
        selectOption('.mouth-options', mouth);
        document.getElementById('mouth-input').value = mouth;
        document.getElementById('mouth-preview').src = 'emoji_assets/mouth/' + mouth + '.png';
    }
    </script>
</body>
</html>