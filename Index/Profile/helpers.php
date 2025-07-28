<?php
function loadUserProfile($conn, $userId) {
    // Step 1: Get the user tag
    $tagStmt = $conn->prepare("SELECT User_tag FROM Users WHERE USER_ID = ?");
    $tagStmt->bind_param("s", $userId);
    $tagStmt->execute();
    $tagResult = $tagStmt->get_result();
    
    if ($tagResult->num_rows === 0) {
        return null; // User not found
    }

    $userTag = $tagResult->fetch_assoc()['User_tag'];

    // Step 2: Based on the tag, prepare the correct query
    switch ($userTag) {
        case 'P':
            $stmt = $conn->prepare("
                SELECT u.USER_ID, u.email, p.username, p.age, p.gender
                FROM Users u 
                JOIN Player p ON u.USER_ID = p.USER_ID 
                WHERE u.USER_ID = ?
            ");
            break;
        
        case 'T':
            $stmt = $conn->prepare("
                SELECT u.USER_ID, u.email, p.username, p.age, p.gender, t.Team_Name, tp2.username AS teammate
                FROM Users u
                JOIN Team t ON u.USER_ID = t.USER_ID
                JOIN Player p ON t.PLAYER_ID = p.PLAYER_ID
                LEFT JOIN Team_Player tp ON t.TEAM_ID = tp.TEAM_ID AND tp.PLAYER_ID != t.PLAYER_ID
                LEFT JOIN Player tp2 ON tp2.PLAYER_ID = tp.PLAYER_ID
                WHERE u.USER_ID = ?
            ");
            break;

        case 'O':
            $stmt = $conn->prepare("
                SELECT u.USER_ID, u.email, o.username, o.Organization_Name
                FROM Users u 
                JOIN Organizers o ON u.USER_ID = o.USER_ID 
                WHERE u.USER_ID = ?
            ");
            break;

        default:
            return null; // Unknown user type
    }

    // Step 3: Execute and return result
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>