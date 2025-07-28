<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name("profile");
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Tournament Finder</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<style>
	<?php 
	include ('Header.css');
	?>
	</style>
</head>
<body>
	<header>
		<div class="Logo">
			<a href="/Tournament Finder/Index/Homepage.php"><img class="logo" src="/Tournament Finder/Index/Images/Logo.png" alt="Logo"></a>
			<h1 class="title">Tournament Finder</h1>
			<div class="Button">
				<ul class="nav-buttons">
					<?php if (isset($_SESSION["USER_ID"])): ?>
						<li><a href="Styling/profilePage.php">Profile</a></li>
						<?php if ($_SESSION["User_tag"] === "O"): ?>
							<li><a href="dashboard.php">Dashboard</a></li>
						<?php elseif ($_SESSION["User_tag"] === "P" || $_SESSION["User_tag"] === "T"): ?>
							<li><a href="userDashboard.php">Dashboard</a></li>
						<?php endif; ?>
						<?php if ($_SESSION["User_tag"] === "O"): ?>
							<li><a href="Styling/TournamentPosting.php">Post</a></li>
						<?php endif; ?>
						<li><a class="logout" href="Profile/LogOut.php">Log Out</a></li>
					<?php else: ?>
						<li><button class="SignUp" onclick="location.href='/Tournament Finder/Index/Profile/SignUp.php'">Sign Up</button></li>
						<li><button class="LogIn" onclick="location.href='/Tournament Finder/Index/Profile/LogIn.php'">Log In</button></li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="search">
				<form method="GET" action="Styling/tournamentList.php">
					<input type="text" name="search" id="searchInput" placeholder="SEARCH..." required />
					<button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
				</form>
			</div>
		</div>
	</header>
	</main>
</body>
</html>