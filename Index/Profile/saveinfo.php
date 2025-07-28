<?php
include_once '../../DATABASE/connectdb.php';

function saveinfo($USER_ID, $email, $password, $User_tag, $extraData) {
    $conn = connectdb();
    mysqli_begin_transaction($conn);

    try {
        // Insert into Users table
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql_user = "INSERT INTO Users (USER_ID, email, password, User_tag) VALUES (?, ?, ?, ?)";
        $stmt_user = mysqli_prepare($conn, $sql_user);
        if (!$stmt_user) {
            throw new Exception("Prepare failed for Users: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt_user, "ssss", $USER_ID, $email, $hashed_password, $User_tag);
        if (!mysqli_stmt_execute($stmt_user)) {
            throw new Exception("Insert failed for Users: " . mysqli_error($conn));
        }

        // PLAYER or TEAM logic
        if ($User_tag === 'P' || $User_tag === 'T') {
            $PLAYER_ID = $extraData['PLAYER_ID'];
            $TEAM_ID = $extraData['TEAM_ID'];
            $age = $extraData['age'];
            $gender = $extraData['gender'];
            $username = $extraData['username'];
            $Team_Name = $extraData['Team_Name'] ?? $username . "'s Team " . rand(100, 999);

            // Insert leader as player
            $sql_player = "INSERT INTO Player (PLAYER_ID, USER_ID, age, gender, username) VALUES (?, ?, ?, ?, ?)";
            $stmt_player = mysqli_prepare($conn, $sql_player);
            if (!$stmt_player) throw new Exception("Prepare failed for Player: " . mysqli_error($conn));

            mysqli_stmt_bind_param($stmt_player, "ssiss", $PLAYER_ID, $USER_ID, $age, $gender, $username);
            if (!mysqli_stmt_execute($stmt_player)) {
                throw new Exception("Insert failed for Player: " . mysqli_error($conn));
            }

            // Insert team
            $sql_team = "INSERT INTO Team (TEAM_ID, USER_ID, PLAYER_ID, Team_Name) VALUES (?, ?, ?, ?)";
            $stmt_team = mysqli_prepare($conn, $sql_team);
            if (!$stmt_team) throw new Exception("Prepare failed for Team: " . mysqli_error($conn));

            mysqli_stmt_bind_param($stmt_team, "ssss", $TEAM_ID, $USER_ID, $PLAYER_ID, $Team_Name);
            if (!mysqli_stmt_execute($stmt_team)) {
                throw new Exception("Insert failed for Team: " . mysqli_error($conn));
            }

            // Link player to team
            $sql_team_player = "INSERT INTO Team_Player (TEAM_ID, PLAYER_ID) VALUES (?, ?)";
            $stmt_team_player = mysqli_prepare($conn, $sql_team_player);
            if (!$stmt_team_player) throw new Exception("Prepare failed for Team_Player: " . mysqli_error($conn));

            mysqli_stmt_bind_param($stmt_team_player, "ss", $TEAM_ID, $PLAYER_ID);
            if (!mysqli_stmt_execute($stmt_team_player)) {
                throw new Exception("Insert failed for Team_Player (leader): " . mysqli_error($conn));
            }

            // Optional: Insert teammate
            if (isset($extraData['teammate'])) {
                $tm = $extraData['teammate'];
                $tm_id = $tm['PLAYER_ID'];
                $tm_username = $tm['username'];
                $tm_age = $tm['age'];
                $tm_gender = $tm['gender'];
                $null_user = null;

                $sql_tm = "INSERT INTO Player (PLAYER_ID, USER_ID, age, gender, username) VALUES (?, ?, ?, ?, ?)";
                $stmt_tm = mysqli_prepare($conn, $sql_tm);
                if (!$stmt_tm) throw new Exception("Prepare failed for Teammate: " . mysqli_error($conn));

                mysqli_stmt_bind_param($stmt_tm, "sisss", $tm_id, $null_user, $tm_age, $tm_gender, $tm_username);
                if (!mysqli_stmt_execute($stmt_tm)) {
                    throw new Exception("Insert failed for Teammate Player: " . mysqli_error($conn));
                }

                $sql_link_tm = "INSERT INTO Team_Player (TEAM_ID, PLAYER_ID) VALUES (?, ?)";
                $stmt_link_tm = mysqli_prepare($conn, $sql_link_tm);
                if (!$stmt_link_tm) throw new Exception("Prepare failed for teammate link: " . mysqli_error($conn));

                mysqli_stmt_bind_param($stmt_link_tm, "ss", $TEAM_ID, $tm_id);
                if (!mysqli_stmt_execute($stmt_link_tm)) {
                    throw new Exception("Link failed for teammate: " . mysqli_error($conn));
                }
            }
        }

        // ORGANIZER logic
        elseif ($User_tag === 'O') {
            $ORGANIZER_ID = $extraData['ORGANIZER_ID'];
            $username = $extraData['username'];
            $Organization_Name = $extraData['Organization_Name'];

            $sql_org = "INSERT INTO Organizers (ORGANIZER_ID, USER_ID, username, Organization_Name)
                        VALUES (?, ?, ?, ?)";
            $stmt_org = mysqli_prepare($conn, $sql_org);
            if (!$stmt_org) throw new Exception("Prepare failed for Organizers: " . mysqli_error($conn));

            mysqli_stmt_bind_param($stmt_org, "ssss", $ORGANIZER_ID, $USER_ID, $username, $Organization_Name);
            if (!mysqli_stmt_execute($stmt_org)) {
                throw new Exception("Insert failed for Organizers: " . mysqli_error($conn));
            }
        }

        mysqli_commit($conn);
        mysqli_close($conn);
        return true;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Signup failed: " . $e->getMessage());
        mysqli_close($conn);
        return false;
    }
}
?>