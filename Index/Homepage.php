<!DOCTYPE html>
<html>
<head>
    <style>
        body{
               background-image: url('../Index/Images/badminton_background.webp');
               background-size: cover;
               background-repeat: no-repeat;
               background-position: center;
               min-height: 100vh;
        }
        
        .main_content{
            padding: 20px;
        }

        h1{
            color: white;
        }

        h2{
            color: white;
        }

        .slider-wrapper {
            position: relative;
            overflow: hidden;
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
            transition: transform 0.2s ease;
        }
        
        .tournament-gallery::-webkit-scrollbar {
            display: none; 
        }

        .tournament-gallery {
            -ms-overflow-style: none;  
            scrollbar-width: none;    
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .arrow-left {
            left: 10px;
        }

        .arrow-right {
            right: 10px;
        }

        .tournament-card:hover {
            transform: scale(1.03);
        }

        .poster-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius:8px;
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
<body class="home">
    <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_name("profile");
            session_start();
        }
        include("Styling/Header.php");

            $timeOptions = [
                "07:00" => "7:00 AM - 3:00 PM",
                "08:00" => "8:00 AM - 4:00 PM",
                "09:00" => "9:00 AM - 5:00 PM",
                "10:00" => "10:00 AM - 6:00 PM",
            ];

            if (!isset($_SESSION["submitted"])){
                $_SESSION["submitted"] = 0;
            }
            else{
                $_SESSION["submitted"]++;
            }
            if ($_SERVER["REQUEST_METHOD"] == "POST")
            {
                if (!isset($_SESSION["PLAYER_ID"]))
                {
                    header("Location: Profile/LogIn.php");
                    exit();
                }
                if ($_SESSION["submitted"] != $_POST["submitted"])
                {
                    header("Location: Homepage.php");
                    exit();
                }
            }
        ?>
    <div class="main_content">
         <h1>Hello, Player!</h1>
        <h2>Upcoming Events</h2>

        <?php
        include_once '../DATABASE/connectdb.php';
        $conn = connectdb();

        $query = "SELECT TOURNAMENT_ID, title, poster, date, StartEndTime, location FROM Tournament ORDER BY date DESC LIMIT 6";
        $result = $conn->query($query);
        if (!$result || $result->num_rows === 0) {
        echo "<p style='color:white;'>No tournaments found or SQL failed.</p>";
        exit;
}
        ?>

        <div class="slider-wrapper">
            <button class="arrow-btn arrow-left" onclick="scrollGallery(-1)">&#8592;</button>
            <div class="tournament-gallery" id="tournamentGallery">
                <?php while ($row = $result->fetch_assoc()): ?>
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
    </div>
<script>
function scrollGallery(direction) {
    const gallery = document.getElementById("tournamentGallery");
    const scrollAmount = 420;

    gallery.scrollBy({
        left: direction * scrollAmount,
        behavior: "smooth"
    });
}
</script>
</body>
</html>