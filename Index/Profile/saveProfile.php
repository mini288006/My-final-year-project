<?php
session_name("profile");
session_start();
require_once("../../DATABASE/connectdb.php");
$conn = connectdb();

$userTag = $_SESSION['User_tag'] ?? '';
$userID = $_SESSION['USER_ID'] ?? '';

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$age = $_POST['age'] ?? null;
$gender = $_POST['gender'] ?? null;
$teamName = $_POST['team_name'] ?? null;
$orgName = $_POST['organization'] ?? null;

// ✅ Update Users table (email only — no 'username' column in Users)
$updateUserSQL = "UPDATE Users SET email = ? WHERE USER_ID = ?";
$stmt = mysqli_prepare($conn, $updateUserSQL);
mysqli_stmt_bind_param($stmt, "ss", $email, $userID);
mysqli_stmt_execute($stmt);

// ✅ Update session
$_SESSION['email'] = $email;
$_SESSION['username'] = $username;

if ($userTag === 'P') {
    // ✅ Update Player table (username, age, gender)
    $updatePlayerSQL = "UPDATE Player SET username = ?, age = ?, gender = ? WHERE USER_ID = ?";
    $stmt = mysqli_prepare($conn, $updatePlayerSQL);
    mysqli_stmt_bind_param($stmt, "siss", $username, $age, $gender, $userID);
    mysqli_stmt_execute($stmt);
}

elseif ($userTag === 'T') {
    // ✅ Update Player table (username, age, gender)
    $updatePlayerSQL = "UPDATE Player SET username = ?, age = ?, gender = ? WHERE USER_ID = ?";
    $stmt = mysqli_prepare($conn, $updatePlayerSQL);
    mysqli_stmt_bind_param($stmt, "siss", $username, $age, $gender, $userID);
    mysqli_stmt_execute($stmt);

    // ✅ Update Team name
    $updateTeamSQL = "UPDATE Team SET Team_Name = ? WHERE USER_ID = ?";
    $stmt = mysqli_prepare($conn, $updateTeamSQL);
    mysqli_stmt_bind_param($stmt, "ss", $teamName, $userID);
    mysqli_stmt_execute($stmt);

    // 🔧 NEW: Update teammate names
    $teammateString = $_POST['teammate'] ?? '';
    $teammateNames = array_map('trim', explode(',', $teammateString));

    // 🔧 Fetch TEAM_ID and current PLAYER_ID
    $teamStmt = $conn->prepare("SELECT TEAM_ID, PLAYER_ID FROM Team WHERE USER_ID = ?");
    $teamStmt->bind_param("s", $userID);
    $teamStmt->execute();
    $teamResult = $teamStmt->get_result();
    $teamRow = $teamResult->fetch_assoc();
    $teamId = $teamRow['TEAM_ID'];
    $currentPlayerId = $teamRow['PLAYER_ID'];

    // 🔧 Get teammate PLAYER_IDs (excluding self)
    $teammatesStmt = $conn->prepare("
        SELECT Player.PLAYER_ID 
        FROM Team_Player 
        JOIN Player ON Team_Player.PLAYER_ID = Player.PLAYER_ID 
        WHERE Team_Player.TEAM_ID = ? AND Player.PLAYER_ID != ?
    ");
    $teammatesStmt->bind_param("ss", $teamId, $currentPlayerId);
    $teammatesStmt->execute();
    $teammatesResult = $teammatesStmt->get_result();

    // 🔧 Update teammate names
    $i = 0;
    while ($row = $teammatesResult->fetch_assoc()) {
        if (isset($teammateNames[$i])) {
            $newName = $teammateNames[$i];
            $targetPlayerId = $row['PLAYER_ID'];

            $updateTmStmt = $conn->prepare("UPDATE Player SET username = ? WHERE PLAYER_ID = ?");
            $updateTmStmt->bind_param("ss", $newName, $targetPlayerId);
            $updateTmStmt->execute();
        }
        $i++;
    }
}

elseif ($userTag === 'O') {
    // ✅ Update Organizer name
    $updateOrgSQL = "UPDATE Organizers SET organization_name = ? WHERE USER_ID = ?";
    $stmt = mysqli_prepare($conn, $updateOrgSQL);
    mysqli_stmt_bind_param($stmt, "ss", $orgName, $userID);
    mysqli_stmt_execute($stmt);

    $_SESSION['org_name'] = $orgName;
}

header("Location: ../Styling/profilePage.php");
exit();