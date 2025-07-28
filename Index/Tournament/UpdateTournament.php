<?php
include_once '../../DATABASE/connectdb.php';
session_name("profile");
session_start();
$conn = connectdb();

if (!isset($_SESSION['User_tag']) || $_SESSION['User_tag'] !== 'O') {
    echo "<p style='color:red;'>Only organizers can update tournaments.</p>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tournamentID = $_POST['tournamentID'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $categoryNames = $_POST['categories'] ?? [];
    $categoryFees = $_POST['categoryFees'] ?? [];

    // Input validation
    if (!$tournamentID || !$title || !$description || !$date || !$time || !$location) {
        echo "<p style='color:red;'>All fields except poster are required.</p>";
        exit;
    }

    // Handle poster image if uploaded
    $poster_filename = null;
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileTmp = $_FILES['poster']['tmp_name'];
        $fileType = mime_content_type($fileTmp);

        if (!in_array($fileType, $allowedTypes)) {
            echo "<p style='color:red;'>Only JPG, PNG, and WEBP files are allowed.</p>";
            exit;
        }

        $poster_filename = uniqid('poster_') . '_' . basename($_FILES['poster']['name']);
        $destination = '../../uploads/' . $poster_filename;

        if (!move_uploaded_file($fileTmp, $destination)) {
            echo "<p style='color:red;'>Failed to upload poster.</p>";
            exit;
        }
    }

    // Update Tournament (with or without poster)
    if ($poster_filename) {
        $sql = "UPDATE Tournament SET title=?, description=?, date=?, StartEndTime=?, location=?, poster=? WHERE TOURNAMENT_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $title, $description, $date, $time, $location, $poster_filename, $tournamentID);
    } else {
        $sql = "UPDATE Tournament SET title=?, description=?, date=?, StartEndTime=?, location=? WHERE TOURNAMENT_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $title, $description, $date, $time, $location, $tournamentID);
    }

    if ($stmt->execute()) {
        // Clear existing categories
        $conn->query("DELETE FROM Tournament_Categories WHERE TOURNAMENT_ID = '" . $conn->real_escape_string($tournamentID) . "'");

        // Insert new categories
        $cat_stmt = $conn->prepare("INSERT INTO Tournament_Categories (ID, TOURNAMENT_ID, Category_Name, Fee) VALUES (?, ?, ?, ?)");
        for ($i = 0; $i < count($categoryNames); $i++) {
            $categoryID = 'cat_' . bin2hex(random_bytes(4));
            $categoryName = $categoryNames[$i];
            $fee = $categoryFees[$i];
            $cat_stmt->bind_param("sssi", $categoryID, $tournamentID, $categoryName, $fee);
            $cat_stmt->execute();
        }

        header("Location: ../Styling/TournamentDetails.php?id=" . urlencode($tournamentID));
        exit;
    } else {
        echo "<p style='color:red;'>Error updating tournament: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p style='color:red;'>Invalid request.</p>";
}
?>