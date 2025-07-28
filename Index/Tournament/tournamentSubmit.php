<?php
session_name("profile");
session_start();

include("../../DATABASE/connectdb.php");
$conn = connectdb();

if (!isset($_SESSION["USER_ID"]) || !isset($_SESSION["User_tag"])) {
    echo "<p style='color:red;'>You must be logged in to register.</p>";
    exit;
}

$userID = $_SESSION["USER_ID"];
$userTag = $_SESSION["User_tag"];
$tournamentID = $_POST["TOURNAMENT_ID"] ?? null;
$categoryIDs = $_POST["category_id"] ?? [];

if (!$tournamentID || empty($categoryIDs)) {
    echo "<p style='color:red;'>Missing tournament or category selection.</p>";
    exit;
}

$participantType = $userTag === 'P' ? 'player' : ($userTag === 'T' ? 'team' : null);
if (!$participantType) {
    echo "<p style='color:red;'>Invalid user type.</p>";
    exit;
}

$registrationID = uniqid("R");

// Insert into Registration
$stmt = $conn->prepare("INSERT INTO Registration (REGISTRATION_ID, TOURNAMENT_ID, USER_ID, Participant_Type) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $registrationID, $tournamentID, $userID, $participantType);

if (!$stmt->execute()) {
    echo "<p style='color:red;'>Registration failed: " . $stmt->error . "</p>";
    exit;
}
$stmt->close();

// Insert into Registration_Categories
$catStmt = $conn->prepare("INSERT INTO Registration_Categories (REGISTRATION_ID, CATEGORY_ID) VALUES (?, ?)");
foreach ($categoryIDs as $catID) {
    $catStmt->bind_param("ss", $registrationID, $catID);
    if (!$catStmt->execute()) {
        echo "<p style='color:red;'>Failed to register category: " . $catStmt->error . "</p>";
        exit;
    }
}
$catStmt->close();

// Insert into Registration_Player or Registration_Team
if ($participantType === 'player') {
    $query = $conn->prepare("SELECT PLAYER_ID FROM Player WHERE USER_ID = ?");
    $query->bind_param("s", $userID);
    $query->execute();
    $query->bind_result($playerID);
    $query->fetch();
    $query->close();

    if ($playerID) {
        $rpStmt = $conn->prepare("INSERT INTO Registration_Player (REGISTRATION_ID, PLAYER_ID) VALUES (?, ?)");
        $rpStmt->bind_param("ss", $registrationID, $playerID);
        if (!$rpStmt->execute()) {
            echo "<p style='color:red;'>Player registration failed: " . $rpStmt->error . "</p>";
            exit;
        }
        $rpStmt->close();
    } else {
        echo "<p style='color:red;'>Player not found.</p>";
        exit;
    }

} elseif ($participantType === 'team') {
    $query = $conn->prepare("SELECT TEAM_ID FROM Team WHERE USER_ID = ?");
    $query->bind_param("s", $userID);
    $query->execute();
    $query->bind_result($teamID);
    $query->fetch();
    $query->close();

    if ($teamID) {
        $rtStmt = $conn->prepare("INSERT INTO Registration_Team (REGISTRATION_ID, TEAM_ID) VALUES (?, ?)");
        $rtStmt->bind_param("ss", $registrationID, $teamID);
        if (!$rtStmt->execute()) {
            echo "<p style='color:red;'>Team registration failed: " . $rtStmt->error . "</p>";
            exit;
        }
        $rtStmt->close();
    } else {
        echo "<p style='color:red;'>Team not found.</p>";
        exit;
    }
}

header("Location: ../userDashboard.php");
exit;
?>
