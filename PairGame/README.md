# Link to the website in the VM:
 - http://ml-lab-4d78f073-aa49-4f0e-bce2-31e5254052c7.ukwest.cloudapp.azure.com:63204/PairGame/index.php
 - NOTE: The website works as intended in local machine's google chrome, there are issues when using VM's firefox

# Navbar
 - Home, Play Pairs, Registered if the user is not registered
 - Home, Play Pairs, Leaderboard, custom emoji if registered

## Index.php & index.css
 - Link to the registration page
 - Welcome message based on user's name if already registered

## Registration.php & registration.css
 - Username cannot contain special characters
 - Custom emoji creation
 - Redirects to Play Pairs page when registration is completed

## Pairs.php & pairs.css
 - Button to start the game
 - Emojis are dynamically created with emoji assets
 - Emoji combination pairs cannot repeat
 - Cards flip and green glow when matched correctly
 - Score based on success of attempts and time spent
 - Background turns gold when reaching the highest score 
 - Game stats in a dashboard to show attempts, time spent, levels completed and total score
 - Option to play again at the end of the game
 - Unregistered players are given a button to register 
 - Registered players are given a button to submit score

## Leaderboard.php & leaderboard.css
 - Fetches JSON and ranks users based on score
 - "Total Scores" tab shows Rank, Player, Score, Levels Completed, Date
 - "Level Scores" shows level scores with a drop-down menu for level selection
 - Time and date of submission is shown by converting a timestamp from leaderboard_data.json

## Leaderboard_data.json
 - Stores formatted data on each submission
 - Used by leaderboard.php and pairs.php



