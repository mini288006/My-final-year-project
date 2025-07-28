<?php
session_name("profile");
session_start();
require_once('../../DATABASE/connectdb.php');
$conn = connectdb();


if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$tournamentID = $_GET['id'] ?? '';
$userID = $_SESSION['USER_ID'] ?? '';
$userTag = $_SESSION['User_tag'] ?? '';

$organizerID = '';
if ($userTag === 'O') {
    $stmtOrg = mysqli_prepare($conn, "SELECT ORGANIZER_ID FROM Organizers WHERE USER_ID = ?");
    mysqli_stmt_bind_param($stmtOrg, "s", $userID);
    mysqli_stmt_execute($stmtOrg);
    $resultOrg = mysqli_stmt_get_result($stmtOrg);
    $rowOrg = mysqli_fetch_assoc($resultOrg);
    $organizerID = $rowOrg['ORGANIZER_ID'] ?? '';
}

if (!$tournamentID) {
    die("Invalid tournament ID");
}

$sql = "SELECT * FROM Tournament WHERE TOURNAMENT_ID = ?";
$sql = "SELECT T.*, O.Organization_Name, O.username AS organizer_name, U.email AS organizer_contact
        FROM Tournament T
        JOIN Organizers O ON T.ORGANIZER_ID = O.ORGANIZER_ID
        JOIN Users U ON O.USER_ID = U.USER_ID
        WHERE T.TOURNAMENT_ID = ?";

$sqlFee = "SELECT MIN(Fee) AS min_fee FROM Tournament_Categories WHERE TOURNAMENT_ID = ?";
$stmtFee = mysqli_prepare($conn, $sqlFee);
mysqli_stmt_bind_param($stmtFee, "s", $tournamentID);
mysqli_stmt_execute($stmtFee);
$resultFee = mysqli_stmt_get_result($stmtFee);
$feeData = mysqli_fetch_assoc($resultFee);
$entryFee = $feeData['min_fee'] ?? 'N/A';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $tournamentID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tournament = mysqli_fetch_assoc($result);

$sqlCategories = "SELECT Category_Name, Fee FROM Tournament_Categories WHERE TOURNAMENT_ID = ?";
$stmtCategories = mysqli_prepare($conn, $sqlCategories);
mysqli_stmt_bind_param($stmtCategories, "s", $tournamentID);
mysqli_stmt_execute($stmtCategories);
$resultCategories = mysqli_stmt_get_result($stmtCategories);

$categories = [];
while ($row = mysqli_fetch_assoc($resultCategories)) {
    $categories[] = $row;
}

if (!$tournament) {
    echo "<pre>Debug Info:\n";
    echo "Tournament ID: $tournamentID\n";
    echo "Query executed but no results found.\n";
    echo "</pre>";
    die("Tournament not found.");
}

$isOrganizer = ($userTag === 'O' && $tournament['ORGANIZER_ID'] == $organizerID);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tournament['title']) ?> - Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Bree+Serif&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0f4f8a;
            padding: 40px;
        }
        .container {
            background: #F0F0F0;
            padding: 30px;
            border-radius: 16px;
            max-width: 800px;
            margin: auto;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .poster {
            max-width: 100%;
            border-radius: 8px;
        }
        .btn {
            margin-top: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 4px 4px 8px rgba(0, 0, 0, 0.5);
        }
        .btn-signup {
            background-color: #2e8b57;
        }
        .btn-edit {
            background-color: #2e8b57;
        }
        .btn-delete {
            background-color: #d9534f;
        }

        .homepage-btn {
            background-color: #2e8b57;
        }

        .homepage-btn:hover{
            background-color: #246b45;
        }

        .btn-signup:hover{
            background-color: #246b45;
        }

        .btn-edit:hover{
            background-color: #246b45;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?= htmlspecialchars($tournament['title']) ?></h2>
        <img class="poster" src="../../uploads/<?= htmlspecialchars($tournament['poster']) ?>" alt="Poster">
        <p><strong>Title:</strong><br><?= nl2br(htmlspecialchars($tournament['title'])) ?></p>
        <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($tournament['description'])) ?></p>
        <?php if (!empty($categories)): ?>
            <p><strong>Categories: <br></strong></p>
            <ul>
                <?php foreach ($categories as $cat): ?>
                    <li><?= htmlspecialchars($cat['Category_Name']) ?> - RM<?= htmlspecialchars($cat['Fee']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><strong>Categories: <br></strong> No categories available for this tournament.</p>
        <?php endif; ?>
        <p><strong>Date: <br></strong> <?= htmlspecialchars(date('Y-m-d', strtotime($tournament['date']))) ?></p>
        <p><strong>Time: <br></strong> <?= htmlspecialchars($tournament['StartEndTime']) ?></p>
        <p><strong>Location: <br></strong> <?= htmlspecialchars($tournament['location']) ?></p>
        <p><strong>Hosted By: </strong> <?= htmlspecialchars($tournament['Organization_Name']) ?></p>
        <p><strong>Organizer:</strong> <?= htmlspecialchars($tournament['organizer_name']) ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($tournament['organizer_contact']) ?></p>

        <?php if ($isOrganizer): ?>
            <button class="btn btn-edit" onclick="location.href='../Tournament/editTournament.php?id=<?= $tournamentID ?>'">Edit</button>
            <button class="btn btn-delete" onclick="if(confirm('Delete this tournament?')) location.href='../Tournament/deleteTournament.php?id=<?= $tournamentID ?>'">Delete</button>
        <?php elseif ($userTag === 'P' || $userTag === 'T'): ?>
            <button class="btn btn-signup" onclick="location.href='../Tournament/signupTournament.php?id=<?= $tournamentID ?>'">Sign Up</button>
        <?php endif; ?>
        <button class="btn homepage-btn" onclick="location.href='../Homepage.php'">Back to Homepage</button>
    </div>
</body>
</html>