<?php
session_name("profile");
session_start();

$errors = [];
$username = "";
$email = "";
$user_tag = $_POST["User_tag"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($user_tag) || !in_array($user_tag, ['P', 'T', 'O'])) {
        $errors[] = "Invalid role selection.";
    }
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        include("../../DATABASE/connectdb.php");
        $conn = connectdb();

        $query = "SELECT USER_ID, User_tag, password FROM Users WHERE email = ? AND User_tag = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $email, $user_tag);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user["password"])) {
                $_SESSION["USER_ID"] = $user["USER_ID"];
                $_SESSION["User_tag"] = $user_tag;
                $_SESSION["email"] = $email;

                // Match role-specific data
                switch ($user_tag) {
                    case "P":
                        $sql = "SELECT PLAYER_ID, username, age, gender FROM Player WHERE USER_ID = ? AND username = ?";
                        break;
                    case "T":
                        $sql = "SELECT t.TEAM_ID, t.Team_Name AS username, t.PLAYER_ID 
                                FROM Team t 
                                JOIN Player p ON t.PLAYER_ID = p.PLAYER_ID 
                                WHERE p.USER_ID = ? AND t.Team_Name = ?";
                        break;
                    case "O":
                        $sql = "SELECT ORGANIZER_ID, username, Organization_Name FROM Organizers WHERE USER_ID = ? AND username = ?";
                        break;
                }

                $stmt_role = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt_role, "ss", $_SESSION["USER_ID"], $username);
                mysqli_stmt_execute($stmt_role);
                $res = mysqli_stmt_get_result($stmt_role);

                if ($data = mysqli_fetch_assoc($res)) {
                    $_SESSION["username"] = $data["username"];
                    switch ($user_tag) {
                        case "P":
                            $_SESSION["PLAYER_ID"] = $data["PLAYER_ID"];
                            $_SESSION["AGE"] = $data["age"];
                            $_SESSION["Gender"] = $data["gender"];
                            break;
                        case "T":
                            $_SESSION["TEAM_ID"] = $data["TEAM_ID"];
                            $_SESSION["PLAYER_ID"] = $data["PLAYER_ID"];
                            break;
                        case "O":
                            $_SESSION["ORGANIZER_ID"] = $data["ORGANIZER_ID"];
                            $_SESSION["org_name"] = $data["Organization_Name"];
                            break;
                    }

                    mysqli_close($conn);
                    header("Location: ../Homepage.php");
                    exit();
                } else {
                    $errors[] = ucfirst($username) . " not found or incorrect username.";
                }
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No user found with this email and role.";
        }

        mysqli_close($conn);
    }
}
?>

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
            top: 0; left: 0; right: 0; bottom: 0;
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
            padding: 20px;
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
            margin-right: 10px;
            padding: 0 15px;
        }
        button:hover {
            background-color: #246b45;
        }
        .dropdown {
            width: 100px;
            border-radius: 5px;
            border: inset #2e8b57 3px;
        }
        input {
            border-radius: 5px;
            border: inset #2e8b57 3px;
            width: 100%;
            height: 30px;
            padding-left: 10px;
        }
        .error-box {
            color: white;
            background-color: red;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 10px;
        }
        @media (max-width: 500px) {
            .form_container {
                width: 90%;
            }
        }
    </style>
</head>
<body>
<div class="form_container">
    <form class="form" method="POST">
        <h1>Log In Page</h1>
        <h2>Welcome Back Champ!</h2>

        <?php if (!empty($errors)) : ?>
            <div class="error-box">
                <?php foreach ($errors as $err) : ?>
                    <p>* <?= htmlspecialchars($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label for="user_tag">Log In as:</label><br>
        <select name="User_tag" class="dropdown" required>
            <option value="P" <?= $user_tag == "P" ? "selected" : "" ?>>Player</option>
            <option value="T" <?= $user_tag == "T" ? "selected" : "" ?>>Team</option>
            <option value="O" <?= $user_tag == "O" ? "selected" : "" ?>>Organizer</option>
        </select><br><br>

        <label for="username">Username</label><br>
        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required placeholder="Username"><br><br>

        <label for="password">Password</label><br>
        <input type="password" name="password" required placeholder="Password"><br><br>

        <label for="email">Email</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required placeholder="Email"><br><br>

        <button type="button" onclick="window.location.href='../Homepage.php'">Back to homepage</button>
        <button type="submit">Welcome Back</button>
    </form>
</div>
</body>
</html>
