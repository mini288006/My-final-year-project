<?php
session_name("profile");
session_start();

include_once '../../DATABASE/connectdb.php';
$conn = connectdb();

if (!isset($_SESSION["USER_ID"]) || !isset($_SESSION["User_tag"])) {
    header("Location: Profile/LogIn.php");
    exit();
}

$userId = $_SESSION["USER_ID"];
$userTag = $_SESSION["User_tag"];
$userData = [];
$teammates = [];

// Fetch main user data
if ($userTag === 'P') {
    $userQuery = $conn->prepare("
        SELECT Player.username, Player.age, Player.gender, Users.email
        FROM Player
        JOIN Users ON Player.USER_ID = Users.USER_ID
        WHERE Player.USER_ID = ?
    ");
} elseif ($userTag === 'T') {
    $userQuery = $conn->prepare("
        SELECT Player.username, Player.age, Player.gender, Users.email, Team.Team_Name
        FROM Player
        JOIN Users ON Player.USER_ID = Users.USER_ID
        JOIN Team ON Player.USER_ID = Team.USER_ID
        WHERE Player.USER_ID = ?
    ");
} elseif ($userTag === 'O') {
    $userQuery = $conn->prepare("
        SELECT Organizers.username, Users.email, Organizers.Organization_Name
        FROM Organizers
        JOIN Users ON Organizers.USER_ID = Users.USER_ID
        WHERE Organizers.USER_ID = ?
    ");
}

$userQuery->bind_param("s", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();

if ($userResult->num_rows === 1) {
    $userData = $userResult->fetch_assoc();
}

// Fetch teammates for team players
if ($userTag === 'T') {
    $teamQuery = $conn->prepare("SELECT TEAM_ID, PLAYER_ID FROM Team WHERE USER_ID = ?");
    $teamQuery->bind_param("s", $userId);
    $teamQuery->execute();
    $teamResult = $teamQuery->get_result();

    if ($teamResult->num_rows === 1) {
        $teamRow = $teamResult->fetch_assoc();
        $teamId = $teamRow['TEAM_ID'];
        $playerId = $teamRow['PLAYER_ID'];

        $teammateQuery = $conn->prepare("
            SELECT Player.username 
            FROM Team_Player 
            JOIN Player ON Team_Player.PLAYER_ID = Player.PLAYER_ID
            WHERE Team_Player.TEAM_ID = ? AND Team_Player.PLAYER_ID != ?
        ");
        $teammateQuery->bind_param("ss", $teamId, $playerId);
        $teammateQuery->execute();
        $teammateResult = $teammateQuery->get_result();

        while ($row = $teammateResult->fetch_assoc()) {
            $teammates[] = $row['username'];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Bree+Serif&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0f4f8a;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .profile-container {
            background-color:#F0F0F0;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            width: 400px;
        }

        h2 {
            font-family: 'Bree Serif', serif;
            margin-bottom: 20px;
        }

        .profile-item {
            margin: 10px 0;
        }

        .profile-label {
            font-weight: bold;
        }

        button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #246b45;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background-color: #1e563f;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>My Profile</h2>

        <div class="profile-item"><span class="profile-label">Username:</span> <br><?= htmlspecialchars($userData['username']) ?></div>
        <div class="profile-item"><span class="profile-label">Email:</span> <br><?= htmlspecialchars($userData['email']) ?></div>

        <?php if ($userTag === 'P' || $userTag === 'T'): ?>
            <div class="profile-item"><span class="profile-label">Age:</span> <br><?= htmlspecialchars($userData['age']) ?></div>
            <div class="profile-item"><span class="profile-label">Gender:</span> <br><?= htmlspecialchars($userData['gender']) ?></div>
        <?php endif; ?>

        <?php if ($userTag === 'T'): ?>
            <div class="profile-item"><span class="profile-label">Team Name:</span> <br><?= htmlspecialchars($userData['Team_Name']) ?></div>
            <div class="profile-item"><span class="profile-label">Teammates:</span><br>
                <?php if (!empty($teammates)): ?>
                    <ul>
                        <?php foreach ($teammates as $teammate): ?>
                            <li><?= htmlspecialchars($teammate) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <span>No teammates found.</span>
                <?php endif; ?>
            </div>
        <?php elseif ($userTag === 'O'): ?>
            <div class="profile-item"><span class="profile-label">Organization:</span> <br><?= htmlspecialchars($userData['Organization_Name']) ?></div>
        <?php endif; ?>

        <button onclick="location.href='../Homepage.php'">Back to Homepage</button>
        <button onclick="location.href='editProfile.php'">Edit Profile</button>
    </div>
</body>
</html>