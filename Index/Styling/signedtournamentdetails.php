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

if (!$tournamentID) {
    die("Invalid tournament ID");
}

// Handle Leave Tournament Request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["leave_tournament"])) {
    $tournamentID = $_POST["tournament_id"];
    $userID = $_SESSION["USER_ID"];

    // Fetch registration ID
    $fetchReg = "SELECT REGISTRATION_ID FROM Registration WHERE USER_ID = ? AND TOURNAMENT_ID = ?";
    $stmtFetch = mysqli_prepare($conn, $fetchReg);
    mysqli_stmt_bind_param($stmtFetch, "ss", $userID, $tournamentID);
    mysqli_stmt_execute($stmtFetch);
    $resultFetch = mysqli_stmt_get_result($stmtFetch);
    $reg = mysqli_fetch_assoc($resultFetch);

    if ($reg) {
        $registrationID = $reg["REGISTRATION_ID"];

        // Delete from related tables
        mysqli_query($conn, "DELETE FROM Registration_Categories WHERE REGISTRATION_ID = '$registrationID'");
        mysqli_query($conn, "DELETE FROM Registration_Player WHERE REGISTRATION_ID = '$registrationID'");
        mysqli_query($conn, "DELETE FROM Registration_Team WHERE REGISTRATION_ID = '$registrationID'");
        mysqli_query($conn, "DELETE FROM Registration WHERE REGISTRATION_ID = '$registrationID'");

        echo "<script>alert('You have left the tournament successfully.'); window.location.href = '../Homepage.php';</script>";
        exit;
    } else {
        echo "<p style='color:red;'>No registration record found to delete.</p>";
    }
}

// Fetch tournament details
$sql = "SELECT T.*, O.Organization_Name, O.username AS organizer_name, U.email AS organizer_contact
        FROM Tournament T
        JOIN Organizers O ON T.ORGANIZER_ID = O.ORGANIZER_ID
        JOIN Users U ON O.USER_ID = U.USER_ID
        WHERE T.TOURNAMENT_ID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $tournamentID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tournament = mysqli_fetch_assoc($result);

// Fetch categories
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
    die("Tournament not found.");
}

// Fetch registration info
$registrationQuery = "";
if ($userTag === 'P') {
    $registrationQuery = "SELECT P.username AS name, TC.Category_Name
                         FROM Registration R
                         JOIN Registration_Player RP ON R.REGISTRATION_ID = RP.REGISTRATION_ID
                         JOIN Player P ON RP.PLAYER_ID = P.PLAYER_ID
                         JOIN Registration_Categories RC ON R.REGISTRATION_ID = RC.REGISTRATION_ID
                         JOIN Tournament_Categories TC ON RC.CATEGORY_ID = TC.ID
                         WHERE R.USER_ID = ? AND R.TOURNAMENT_ID = ?";
} elseif ($userTag === 'T') {
    $registrationQuery = "SELECT T.Team_Name AS name, TC.Category_Name
                         FROM Registration R
                         JOIN Registration_Team RT ON R.REGISTRATION_ID = RT.REGISTRATION_ID
                         JOIN Team T ON RT.TEAM_ID = T.TEAM_ID
                         JOIN Registration_Categories RC ON R.REGISTRATION_ID = RC.REGISTRATION_ID
                         JOIN Tournament_Categories TC ON RC.CATEGORY_ID = TC.ID
                         WHERE R.USER_ID = ? AND R.TOURNAMENT_ID = ?";
}

$registeredName = $categoryName = null;
if ($registrationQuery) {
    $stmtReg = mysqli_prepare($conn, $registrationQuery);
    mysqli_stmt_bind_param($stmtReg, "ss", $userID, $tournamentID);
    mysqli_stmt_execute($stmtReg);
    $resultReg = mysqli_stmt_get_result($stmtReg);
    if ($row = mysqli_fetch_assoc($resultReg)) {
        $registeredName = $row['name'];
        $categoryName = $row['Category_Name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tournament['title']) ?> - Registered Details</title>
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
        .homepage-btn {
            background-color: #2e8b57;
        }
        .homepage-btn:hover {
            background-color: #246b45;
        }
        .leave-btn {
            background-color: #d9534f;
        }
        .leave-btn:hover {
            background-color: #c9302c;
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
            <p><strong>Categories: <br></strong> No categories available.</p>
        <?php endif; ?>

        <p><strong>Date:</strong><br><?= htmlspecialchars(date('Y-m-d', strtotime($tournament['date']))) ?></p>
        <p><strong>Time:</strong><br><?= htmlspecialchars($tournament['StartEndTime']) ?></p>
        <p><strong>Location:</strong><br><?= htmlspecialchars($tournament['location']) ?></p>
        <p><strong>Hosted By:</strong> <?= htmlspecialchars($tournament['Organization_Name']) ?></p>
        <p><strong>Organizer:</strong> <?= htmlspecialchars($tournament['organizer_name']) ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($tournament['organizer_contact']) ?></p>

        <button class="btn homepage-btn" onclick="location.href='../Homepage.php'">Back to Homepage</button>

        <?php if ($registeredName && $categoryName): ?>
            <p><strong>You have registered as:</strong> <?= htmlspecialchars($registeredName) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($categoryName) ?></p>

            <form method="post" onsubmit="return confirm('Are you sure you want to leave this tournament?');">
                <input type="hidden" name="tournament_id" value="<?= htmlspecialchars($tournamentID) ?>">
                <input type="hidden" name="leave_tournament" value="1">
                <button type="submit" class="btn leave-btn">Leave Tournament</button>
            </form>
        <?php else: ?>
            <p style="color: red;">Registration not found for this tournament.</p>
        <?php endif; ?>
    </div>
</body>
</html>
