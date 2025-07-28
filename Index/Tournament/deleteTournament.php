<?php
session_name("profile");
session_start();

include("../../DATABASE/connectdb.php");
$conn = connectdb();

if (!isset($_SESSION['User_tag']) || $_SESSION['User_tag'] !== 'O') {
    echo "<p style='color:red;'>Only organizers can delete tournaments.</p>";
    exit;
}

if (!isset($_GET['id'])) {
    echo "<p style='color:red;'>No tournament ID provided.</p>";
    exit;
}

$tournament_id = $_GET['id'];

// Step 1: Delete from Registration_Player
$stmt = $conn->prepare("DELETE FROM Registration_Player 
    WHERE REGISTRATION_ID IN (
        SELECT REGISTRATION_ID FROM Registration WHERE TOURNAMENT_ID = ?
    )");
$stmt->bind_param("s", $tournament_id);
$stmt->execute();
$stmt->close();

// Step 2: Delete from Registration_Team
$stmt = $conn->prepare("DELETE FROM Registration_Team 
    WHERE REGISTRATION_ID IN (
        SELECT REGISTRATION_ID FROM Registration WHERE TOURNAMENT_ID = ?
    )");
$stmt->bind_param("s", $tournament_id);
$stmt->execute();
$stmt->close();

// Step 3: Delete from Registration_Categories (this must come before Registration)
$stmt = $conn->prepare("DELETE FROM Registration_Categories 
    WHERE REGISTRATION_ID IN (
        SELECT REGISTRATION_ID FROM Registration WHERE TOURNAMENT_ID = ?
    )");
$stmt->bind_param("s", $tournament_id);
$stmt->execute();
$stmt->close();

// Step 4: Delete from Registration
$stmt = $conn->prepare("DELETE FROM Registration WHERE TOURNAMENT_ID = ?");
$stmt->bind_param("s", $tournament_id);
$stmt->execute();
$stmt->close();

// Step 5: Delete from Tournament_Categories
$stmt = $conn->prepare("DELETE FROM Tournament_Categories WHERE TOURNAMENT_ID = ?");
$stmt->bind_param("s", $tournament_id);
$stmt->execute();
$stmt->close();

// Step 6: Delete from Tournament
$stmt = $conn->prepare("DELETE FROM Tournament WHERE TOURNAMENT_ID = ?");
$stmt->bind_param("s", $tournament_id);
if ($stmt->execute()) {
    echo "<p style='color:green;'>Tournament deleted successfully.</p>";
} else {
    echo "<p style='color:red;'>Error deleting tournament.</p>";
}
$stmt->close();
$conn->close();

// Optional: redirect
header("Location: ../Homepage.php");
exit();
?>