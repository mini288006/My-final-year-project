<?php
session_name("profile");
session_start();

include("../../DATABASE/connectdb.php");
$conn = connectdb();

if (!isset($_SESSION["USER_ID"])) {
    echo "<p style='color:red;'>You must be logged in to sign up.</p>";
    exit;
}

$userTag = $_SESSION['User_tag'] ?? null;

if ($userTag === 'P') {
    // Player
    $playerID = $_SESSION['PLAYER_ID'] ?? null;
    if (!$playerID) {
        echo "<p style='color:red;'>Player ID not found in session.</p>";
        exit;
    }

    // FETCH PLAYER NAME + EMAIL
    $stmt = $conn->prepare("
        SELECT p.username, u.email
        FROM Player p
        JOIN Users u ON p.USER_ID = u.USER_ID
        WHERE p.PLAYER_ID = ?
    ");
    $stmt->bind_param("s", $playerID);
    $stmt->execute();
    $stmt->bind_result($playerName, $playerEmail);
    $stmt->fetch();
    $stmt->close();

} elseif ($_SESSION['User_tag'] === 'T') {
    $teamID = $_SESSION['TEAM_ID'] ?? null;
    if (!$teamID) {
        echo "<p style='color:red;'>Team ID not found in session.</p>";
        exit;
    }

    // Get team leader's username and email
    $stmt = $conn->prepare("
        SELECT p.username, u.email
        FROM Team t
        JOIN Users u ON t.USER_ID = u.USER_ID
        JOIN Player p ON u.USER_ID = p.USER_ID
        WHERE t.TEAM_ID = ?
    ");
    $stmt->bind_param("s", $teamID);
    $stmt->execute();
    $stmt->bind_result($playerName, $playerEmail);
    $stmt->fetch();
    $stmt->close();

    $_SESSION['username'] = $playerName;
    $_SESSION['email'] = $playerEmail;

} else {
    echo "<p style='color:red;'>Unauthorized user type.</p>";
    exit;
}

$tournamentID = $_GET['id'] ?? null;

if (!$tournamentID) {
    echo "<p style='color:red;'>Invalid tournament ID.</p>";
    exit;
}

// Fetch tournament data
$stmt = $conn->prepare("SELECT title, poster FROM Tournament WHERE TOURNAMENT_ID = ?");
$stmt->bind_param("s", $tournamentID);
$stmt->execute();
$stmt->bind_result($title, $poster);
$stmt->fetch();
$stmt->close();

// Fetch categories
$categoryQuery = $conn->prepare("SELECT ID, Category_Name, Fee FROM Tournament_Categories WHERE TOURNAMENT_ID = ?");
$categoryQuery->bind_param("s", $tournamentID);
$categoryQuery->execute();
$categories = $categoryQuery->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up Tournament</title>
    <style>
        body {
            background-color: #0f4f8a;
            font-family: Arial, sans-serif;
        }

        .signup-container {
            max-width: 600px;
            margin: 40px auto;
            background: #F0F0F0;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 0 10px #ccc;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 8px;
            margin-top: 6px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .poster-preview {
            text-align: center;
        }

        .poster-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .category-box {
            margin-bottom: 15px;
        }

        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
<div class="signup-container">
    <h2><?php echo htmlspecialchars($title); ?></h2>

    <div class="poster-preview">
        <img src="../../uploads/<?php echo htmlspecialchars($poster); ?>" alt="Tournament Poster">
    </div>

    <form action="tournamentSubmit.php" method="POST">
    <input type="hidden" name="TOURNAMENT_ID" value="<?php echo htmlspecialchars($tournamentID); ?>">

    <?php if ($_SESSION['User_tag'] === 'P'): ?>
    <!-- PLAYER FORM -->
    <label for="username">Fullname:</label>
    <input type="text" name="fullname" value="<?php echo htmlspecialchars($playerName); ?>" readonly>

    <label for="contact">Contact Info (email or phone):</label>
    <input type="text" name="contact" value="<?php echo htmlspecialchars($playerEmail); ?>" required>

<?php elseif ($_SESSION['User_tag'] === 'T'): ?>
    <!-- TEAM FORM -->
    <label for="username">Team Leader:</label>
    <input type="text" name="username" value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" readonly>

    <label for="contact">Leader Contact:</label>
    <input type="text" name="contact" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" required>

    <label for="teammate_name">Teammate Name:</label>
    <input type="text" name="teammate_name" id="teammate_name" placeholder="Enter teammate's name" required>

    <label for="teammate_contact">Teammate Contact:</label>
    <input type="text" name="teammate_contact" id="teammate_contact" placeholder="Enter teammate's contact" required>

<?php else: ?>
    <p style="color: red;">Only Players and Teams can sign up for tournaments.</p>
    <?php exit; ?>
<?php endif; ?>

    <label>Choose Category:</label>
    <?php while ($row = $categories->fetch_assoc()): ?>
        <div class="category-box">
            <input type="checkbox" name="category_id[]" id="cat_<?php echo $row['ID']; ?>" value="<?php echo $row['ID']; ?>">
            <label for="cat_<?php echo $row['ID']; ?>">
                <?php echo htmlspecialchars($row['Category_Name']) . " (RM" . $row['Fee'] . ")"; ?>
            </label>
        </div>
    <?php endwhile; ?>

    <div style="display: flex; justify-content: space-between; gap: 10px;">
        <button type="submit" class="submit-btn" style="flex: 1;">Submit Registration</button>
        
        <a href="../Styling/TournamentDetails.php?id=<?php echo htmlspecialchars($tournamentID); ?>" 
        class="submit-btn" 
        style="text-align: center; background-color: #d9534f;; color: whitek; text-decoration: none; flex: 1;">
        Cancel
        </a>
    </div>
</form>

<script>
function toggleTeammateFields() {
    const participantType = document.getElementById("participant_type").value;
    const teammateFields = document.getElementById("teammateFields");
    
    if (participantType === "team") {
        teammateFields.style.display = "block";
        document.getElementById("teammate_name").required = true;
        document.getElementById("teammate_contact").required = true;
    } else {
        teammateFields.style.display = "none";
        document.getElementById("teammate_name").required = false;
        document.getElementById("teammate_contact").required = false;
    }
}
</script>
</div>
</body>
</html>