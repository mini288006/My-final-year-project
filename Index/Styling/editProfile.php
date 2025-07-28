<!DOCTYPE html>
<html lang="en">
<?php
session_name("profile");
session_start();
require_once('../../DATABASE/connectdb.php');
$conn = connectdb(); 

$userTag = $_SESSION['User_tag'] ?? '';
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$userID = $_SESSION['USER_ID'] ?? '';

$orgName = $age = $gender = $teamName = $teammate = "";

if ($userTag === 'P') {
    $sql = "SELECT age, gender FROM Player WHERE USER_ID = ?";
} elseif ($userTag === 'T') {
    // Get player info and team name
    $sql = "
        SELECT Player.age, Player.gender, Team.Team_Name 
        FROM Player
        JOIN Team ON Player.USER_ID = Team.USER_ID
        WHERE Player.USER_ID = ?
    ";
} elseif ($userTag === 'O') {
    $sql = "SELECT organization_name FROM Organizers WHERE USER_ID = ?";
}

if (isset($sql)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    if ($userTag === 'P' || $userTag === 'T') {
        $age = $data['age'] ?? '';
        $gender = $data['gender'] ?? '';
    }

    if ($userTag === 'T') {
        $teamName = $data['Team_Name'] ?? '';

        // Get teammate(s)
        $teammates = [];

        $teamIdQuery = $conn->prepare("SELECT TEAM_ID, PLAYER_ID FROM Team WHERE USER_ID = ?");
        $teamIdQuery->bind_param("s", $userID);
        $teamIdQuery->execute();
        $teamIdResult = $teamIdQuery->get_result();

        if ($teamIdRow = $teamIdResult->fetch_assoc()) {
            $teamId = $teamIdRow['TEAM_ID'];
            $playerId = $teamIdRow['PLAYER_ID'];

            $tmQuery = $conn->prepare("
                SELECT Player.username 
                FROM Team_Player 
                JOIN Player ON Team_Player.PLAYER_ID = Player.PLAYER_ID 
                WHERE Team_Player.TEAM_ID = ? AND Team_Player.PLAYER_ID != ?
            ");
            $tmQuery->bind_param("ss", $teamId, $playerId);
            $tmQuery->execute();
            $tmResult = $tmQuery->get_result();
            while ($row = $tmResult->fetch_assoc()) {
                $teammates[] = $row['username'];
            }
        }

        $teammate = implode(', ', $teammates); // comma-separated teammate names
    }

    if ($userTag === 'O') {
        $orgName = $data['organization_name'] ?? '';
    }
}
?>
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Bree+Serif&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0f4f8a;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            min-height: 100vh;
        }

        .profile-container {
            max-width: 400px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-family: 'Bree Serif', serif;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-family: 'Bree Serif', serif;
        }

        input[type="text"], input[type="email"], input[type="number"], select {
            width: 350px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .save-btn {
            margin: 30px auto 0;
            padding: 10px 25px;
            background-color: #246b45;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }

        .save-btn:hover {
            background-color: #1e5e3a;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Edit Profile</h2>
        <form action="../Profile/saveProfile.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($username) ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
            </div>

            <?php if ($userTag === 'P' || $userTag === 'T'): ?>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" value="<?= htmlspecialchars($age) ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ($userTag === 'T'): ?>
                <div class="form-group">
                    <label>Team Name</label>
                    <input type="text" name="team_name" value="<?= htmlspecialchars($teamName) ?>">
                </div>
                <div class="form-group">
                    <label>Teammate(s)</label>
                    <input type="text" name="teammate" value="<?= htmlspecialchars($teammate) ?>">
                </div>
            <?php endif; ?>

            <?php if ($userTag === 'O'): ?>
                <div class="form-group">
                    <label>Organization Name</label>
                    <input type="text" name="organization" value="<?= htmlspecialchars($orgName) ?>">
                </div>
            <?php endif; ?>

            <button type="submit" class="save-btn">Save Changes</button>
            <button type="button" class="save-btn" onclick="location.href='profilePage.php'">Cancel</button>
        </form>
    </div>
</body>
</html>
