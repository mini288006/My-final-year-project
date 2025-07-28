<?php
session_name("profile");
session_start();

include("../../DATABASE/connectdb.php");
$conn = connectdb();

$searchTerm = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";

$timeOptions = [
    "07:00" => "7:00 AM - 3:00 PM",
    "08:00" => "8:00 AM - 4:00 PM",
    "09:00" => "9:00 AM - 5:00 PM",
    "10:00" => "10:00 AM - 6:00 PM",
];

$sql = "SELECT TOURNAMENT_ID, title, poster, date, StartEndTime, location FROM Tournament WHERE title LIKE ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tournament List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-image: url('../Images/badminton_background.webp');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        .main_content {
            padding: 20px;
        }

        h1, h2 {
            color: white;
        }

        .tournament-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .tournament-card {
            width: 300px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            transition: transform 0.2s ease;
        }

        .tournament-card:hover {
            transform: scale(1.03);
        }

        .poster-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }

        label {
            font-family: 'Bree Serif', serif;
            font-style: italic;
            font-size: 14px;
            color: gray;
        }

        h3 {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
    <?php include("../Styling/Header.php"); ?>

    <div class="main_content">
        <h1>Search Results</h1>
        <?php if ($result->num_rows > 0): ?>
            <div class="tournament-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="tournament-card">
                        <a href="TournamentDetails.php?id=<?php echo urlencode($row['TOURNAMENT_ID']); ?>">
                            <img src="../../uploads/<?php echo htmlspecialchars($row['poster']); ?>" alt="Tournament Poster" class="poster-img">
                        </a>
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <label>Date: <?php echo date('Y-m-d', strtotime($row['date'])); ?></label><br>
                        <label>Time: <?php echo $timeOptions[$row['StartEndTime']] ?? htmlspecialchars($row['StartEndTime']); ?></label><br>
                        <label>Location: <?php echo htmlspecialchars($row['location']); ?></label>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color:white;">No tournaments found.</p>
        <?php endif; ?>
    </div>
</body>
</html>