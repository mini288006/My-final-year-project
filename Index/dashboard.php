<?php
session_name("profile");
session_start();

include("../DATABASE/connectdb.php");
$conn = connectdb();

// Check if the user is an organizer and ORGANIZER_ID is set
if (!isset($_SESSION["User_tag"]) || $_SESSION["User_tag"] !== "O" || !isset($_SESSION["ORGANIZER_ID"])) {
    echo "<p style='color:red;'>You must be logged in as an organizer to view your tournaments.</p>";
    exit;
}

$organizerID = $_SESSION["ORGANIZER_ID"];

// Get tournaments posted by this organizer
$sql = "SELECT * FROM Tournament WHERE ORGANIZER_ID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $organizerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard</title>
    <style>
        /* your styles remain unchanged */
        body{
            background-image: url('../Index/Images/badminton_background.webp');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            min-height: 100vh;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('Styling/Images/badminton_background.webp'); 
            background-size: cover;
            background-position: center;
            filter: blur(5px);
            z-index: -1;
        }

        h1{
            color: white;
        }

        .main_content {
            padding: 20px;
        }

        .tournament-gallery {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 10px 40px;
        }

        .tournament-card {
            flex: 0 0 auto;
            width: 400px;
            height: 350px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .poster-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .arrow-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(255,255,255,0.8);
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            z-index: 2;
        }

        .arrow-left {
            left: 10px;
        }

        .arrow-right {
            right: 10px;
        }

        label{
            font-family: 'Bree Serif', serif;
            font-style: italic;
            font-size: 14px;
            color: gray;
        }
        h3{
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
<?php include ("Styling/Header.php"); ?>

<div class="main_content">
    <h1>My Tournaments</h1>
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <div class="slider-wrapper">
            <button class="arrow-btn arrow-left" onclick="scrollGallery(-1)">&#8592;</button>
            <div class="tournament-gallery" id="tournamentGallery">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php $tournamentID = $row['TOURNAMENT_ID']; ?>
                    <div class="tournament-card">
                        <a href="Styling/TournamentDetails.php?id=<?php echo urlencode($row['TOURNAMENT_ID']); ?>">
                            <img src="../uploads/<?php echo htmlspecialchars($row['poster']); ?>" alt="Tournament Poster" class="poster-img">
                        </a>
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <label>Date: <?php echo date('Y-m-d', strtotime($row['date'])); ?></label>
                        <label>Time: <?php echo $timeOptions[$row['StartEndTime']] ?? htmlspecialchars($row['StartEndTime']); ?></label><br>
                        <label>Location: <?php echo htmlspecialchars($row['location']); ?></label>
                    </div>
                <?php endwhile; ?>
            </div>
            <button class="arrow-btn arrow-right" onclick="scrollGallery(1)">&#8594;</button>
        </div>
    <?php else: ?>
        <p style="color: white;">You have not posted any tournaments yet.</p>
    <?php endif; ?>
</div>

<script>
    function scrollGallery(direction) {
        const gallery = document.getElementById("tournamentGallery");
        const scrollAmount = 420;
        gallery.scrollBy({ left: direction * scrollAmount, behavior: "smooth" });
    }
</script>
</body>
</html>
