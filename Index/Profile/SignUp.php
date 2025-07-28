<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            background-image: url('../Images/badminton_background.webp');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../Images/badminton_background.webp');
            background-size: cover;
            background-position: center;
            filter: blur(5px);
            z-index: -1;
        }

        .form_container {
            background-color: #1c86f5;
            border-radius: 25px;
            width: 450px;
            text-align: left;
            box-shadow: 2px 7px 8px rgba(0, 0, 0, 0.5);
        }

        .form {
            padding: 10px;
        }

        h1, h2, label {
            color: white;
        }

        button {
            background-color: #2e8b57;
            border: none;
            color: white;
            font-size: 20px;
            border-radius: 8px;
            height: 35px;
            box-shadow: 4px 4px 8px rgba(0, 0, 0, 0.5);
        }

        button:hover {
            background-color: #246b45;
        }

        input, .dropdown {
            border-radius: 5px;
            border: inset #2e8b57 3px;
        }

        #imageUpload {
            display: none;
        }

        img {
            width: 100px;
            height: 100px;
        }
    </style>
</head>
<body>

<?php
session_name("profile");
session_start();

if (!isset($_SESSION["submitted"])) {
    $_SESSION["submitted"] = 0;
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST["submitted"]) || $_POST["submitted"] != $_SESSION["submitted"] + 1) {
        header("Location: SignUp.php");
        exit();
    }

    $_SESSION["submitted"]++;

    $user_tag = $_POST["User_tag"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $email = $_POST["email"];
    $gender = $_POST["gender"] ?? '';
    $organizer_id = '';
    $player_id = '';
    $team_id = '';

    $username = trim($_POST['username']);

    if (!preg_match("/^[A-Za-z]+(?:\s[A-Za-z]+)*$/", $username)) {
        $errors[] = "The name $username is invalid.";
    }

    // Validate password
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $password)) {
        $errors[] = "Password must be at least 8 characters, include upper/lowercase and a number.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate age and gender only for Player and Team
    if ($user_tag !== 'O') {
        $age = $_POST['age'];
        if (empty($age) || !is_numeric($age) || $age < 10 || $age > 100) {
            $errors[] = "Age must be a number between 10 and 100.";
        }

        if (empty($gender)) {
            $errors[] = "Gender is required.";
        }
    }

    // If no errors so far, proceed
    if (empty($errors)) {
        include("../../DATABASE/connectdb.php");
        include("saveinfo.php");

        $user_id = uniqid("U");
        $extraData = [];

        if ($user_tag === 'P') {
            $player_id = uniqid("P");
            $team_id = uniqid("T");
            $extraData = [
                'PLAYER_ID' => $player_id,
                'TEAM_ID' => $team_id,
                'age' => $age,
                'gender' => $gender,
                'username' => $username,
                'Team_Name' => !empty($_POST["Team_Name"]) ? $_POST["Team_Name"] : $username . "'s Team"
            ];
        } elseif ($user_tag === 'T') {
            $team_id = uniqid("T");
            $player_id = uniqid("P");
            $extraData = [
                'PLAYER_ID' => $player_id,
                'TEAM_ID' => $team_id,
                'age' => $_POST['age'],
                'gender' => $_POST['gender'],
                'username' => $_POST['username'],
                'Team_Name' => $_POST["Team_Name"]
            ];
        } elseif ($user_tag === 'O') {
            if (empty($_POST["Organization_Name"])) {
                $errors[] = "Organization Name is required for organizers.";
            } else {
                $organizer_id = uniqid("O");
                $extraData = [
                    'ORGANIZER_ID' => $organizer_id,
                    'username' => $username,
                    'Organization_Name' => $_POST["Organization_Name"]
                ];
            }
        }

        // Add teammate info if entered
        if (($user_tag === 'P' || $user_tag === 'T') &&
            !empty($_POST['Teammate_Username']) &&
            !empty($_POST['Teammate_Age']) &&
            !empty($_POST['Teammate_Gender'])) {

            $extraData['teammate'] = [
                'PLAYER_ID' => uniqid("P"),
                'username' => $_POST['Teammate_Username'],
                'age' => $_POST['Teammate_Age'],
                'gender' => $_POST['Teammate_Gender']
            ];
        }

        // Save to DB
        if (empty($errors) && saveinfo($user_id, $email, $password, $user_tag, $extraData)) {
            $_SESSION["USER_ID"] = $user_id;
            $_SESSION["User_tag"] = $user_tag;

            if ($user_tag == 'P') $_SESSION["PLAYER_ID"] = $player_id;
            if ($user_tag == 'T') $_SESSION["TEAM_ID"] = $team_id;
            if ($user_tag == 'O') $_SESSION["ORGANIZER_ID"] = $organizer_id;

            header("Location: ../Homepage.php");
            exit();
        } else {
            $errors[] = "Something went wrong while saving your information.";
        }
    }
}
?>

<div class="form_container">
    <form class="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <h1>Sign Up page</h1>
        <h2>Welcome To Tournament Finder</h2>

        <?php if (!empty($errors)) : ?>
            <div style="color: white; background-color: red; padding: 10px; border-radius: 10px; margin-bottom: 10px;">
                <ul>
                    <?php foreach ($errors as $error) : ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="UserInput">
            <label for="user_tag">Register As:</label><br>
            <select name="User_tag" class="dropdown" id="user_tag" required onchange="toggleRoleFields()">
                <option value="P" selected>Player</option>
                <option value="T">Team</option>
                <option value="O">Organizer</option>
            </select><br><br>

            <label for="Username">Your Fullname</label><br>
            <input type="text" name="username" placeholder="Fullname" required><br><br>

            <div id="personalFields">
                <label for="Age">Your Age</label><br>
                <input type="number" id="age" name="age" min="10" max="100" placeholder="Age" required><br><br>

                <label for="Gender">Your Gender</label><br>
                <input type="radio" id="male" name="gender" value="male" required>
                <label for="male">Male</label><br>
                <input type="radio" id="female" name="gender" value="female" required>
                <label for="female">Female</label><br><br>

                <label for="Team_Name">Team Name</label><br>
                <input type="text" name="Team_Name" placeholder="Your Team Name/Club"><br><br>
            </div>

            <label for="password">Your Password</label><br>
            <input type="password" name="password" required minlength="8" maxlength="20" placeholder="Password"><br><br>
            <p style="font-size: 12px; color: white; margin-top: -15px;">
                Password must be at least 8 characters long, contain one uppercase letter, one lowercase letter and one number
            </p><br>

            <label for="Email">Your Email</label><br>
            <input type="email" name="email" placeholder="Email" required><br><br>

            <div id="teamFields" style="display: none;">
                <label for="Teammate_Username">Teammate Name</label><br>
                <input type="text" name="Teammate_Username" placeholder="Your Teammate's Name"
                       pattern="[A-Za-z]+((\s)?(['|\-\.]?[A-Za-z]+))*"><br><br>

                <label for="Teammate_Age">Teammate Age</label><br>
                <input type="number" name="Teammate_Age" placeholder="Age" min="10" max="100"><br><br>

                <label for="Teammate_Gender">Teammate Gender</label><br>
                <input type="radio" id="tmale" name="Teammate_Gender" value="male">
                <label for="tmale">Male</label><br>
                <input type="radio" id="tfemale" name="Teammate_Gender" value="female">
                <label for="tfemale">Female</label><br><br>
            </div>

            <div id="organizerFields" style="display: none;">
                <label for="Organization_Name">Organization Name</label><br>
                <input type="text" name="Organization_Name" placeholder="Your Organization Name" required><br><br>
            </div>

            <button type="button" onclick="location.href='../Homepage.php'">Back to homepage</button>
            <button type="submit">Sign Up!!</button>
            <input type="hidden" name="submitted" value="<?php echo $_SESSION["submitted"] + 1; ?>">
        </div>
    </form>
</div>

<script>
    function toggleRoleFields() {
        const role = document.getElementById("user_tag").value;

        // Show/hide sections
        document.getElementById("teamFields").style.display = (role === 'T') ? 'block' : 'none';
        document.getElementById("organizerFields").style.display = (role === 'O') ? 'block' : 'none';
        document.getElementById("personalFields").style.display = (role === 'O') ? 'none' : 'block';

        // Organizer-specific field
        const orgName = document.querySelector("input[name='Organization_Name']");
        if (role === 'O') {
            orgName.required = true;
        } else {
            orgName.required = false;
        }

        // Age
        const ageInput = document.querySelector("input[name='age']");
        if (role === 'O') {
            ageInput.required = false;
        } else {
            ageInput.required = true;
        }

        // Gender
        const genderRadios = document.querySelectorAll("input[name='gender']");
        genderRadios.forEach(r => r.required = (role !== 'O'));

        // Teammate fields (only for Team)
        const teamFields = document.getElementById("teamFields");
        const teammateInputs = teamFields.querySelectorAll("input");
        teammateInputs.forEach(input => {
            if (role === 'T') {
                input.removeAttribute('disabled');
            } else {
                input.setAttribute('disabled', 'disabled');
            }
        });
    }

    // Run once on page load
    window.onload = toggleRoleFields;
</script>

</body>
</html>