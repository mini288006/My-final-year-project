<?php
include_once '../../DATABASE/connectdb.php';
session_name("profile");
session_start();
$conn = connectdb();

if (!isset($_SESSION['User_tag']) || $_SESSION['User_tag'] !== 'O') {
    echo "<p style='color:red;'>Only organizers can edit tournaments.</p>";
    exit;
}

if (!isset($_GET['id'])) {
    echo "<p style='color:red;'>No tournament ID provided.</p>";
    exit;
}

$tournamentID = $_GET['id'];

// Fetch tournament info
$stmt = $conn->prepare("SELECT T.*, O.Organization_Name, O.username AS organizer_name, U.email AS organizer_contact
                        FROM Tournament T
                        JOIN Organizers O ON T.ORGANIZER_ID = O.ORGANIZER_ID
                        JOIN Users U ON O.USER_ID = U.USER_ID
                        WHERE T.TOURNAMENT_ID = ?");
$stmt->bind_param("s", $tournamentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red;'>Tournament not found.</p>";
    exit;
}

$tournament = $result->fetch_assoc();
$stmt->close();

// Fetch categories
$catStmt = $conn->prepare("SELECT ID, Category_Name, Fee FROM Tournament_Categories WHERE TOURNAMENT_ID = ?");
$catStmt->bind_param("s", $tournamentID);
$catStmt->execute();
$catResult = $catStmt->get_result();
$categories = [];
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row;
}
$catStmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Tournament</title>
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

        input[type="text"],
        input[type="time"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0 20px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }

        input[type="date"] {
            width: 100px;
        }

        textarea {
            height: 200px;
        }

        label {
            font-weight: bold;
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

        .btn-save {
            background-color: #2e8b57;
        }

        .btn-cancel {
            background-color: #d9534f;
        }

        .btn-save:hover {
            background-color: #246b45;
        }

        .btn-cancel:hover {
            background-color: #c9302c;
        }

        .category-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .category-item input[type="text"],
        .category-item input[type="number"] {
            flex: 1;
        }
        .category-item button {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Tournament</h2>
    <form action="UpdateTournament.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="tournamentID" value="<?= htmlspecialchars($tournamentID) ?>">

        <label>Title:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($tournament['title']) ?>" required>

        <label>Description:</label>
        <textarea name="description" rows="5" required><?= htmlspecialchars($tournament['description']) ?></textarea>

        <label for="calendar"><strong>Date:</strong></label><br>
        <input type="date" name="date" id="calendar" value="<?= htmlspecialchars($tournament['date']) ?>" required><br><br>

        <label>Time:</label><br>
        <select name="time" required>
            <?php
            $times = ["7:00 AM - 3:00 PM", "8:00 AM - 4:00 PM", "9:00 AM - 5:00 PM", "10:00 AM - 6:00 PM"];
            foreach ($times as $t) {
                $selected = $t === $tournament['StartEndTime'] ? 'selected' : '';
                echo "<option value=\"$t\" $selected>$t</option>";
            }
            ?>
        </select><br><br>

        <label>Location:</label>
        <textarea name="location" rows="2" required><?= htmlspecialchars($tournament['location']) ?></textarea>

        <input type="file" name="poster" id="posterInput" accept="image/*"><br>
        <img id="posterPreview" src="../../uploads/<?= htmlspecialchars($tournament['poster']) ?>" alt="Current Poster" style="max-width: 100%; margin-top: 10px; border-radius: 8px;">
        <br>

        <label>Organizer Name:</label>
        <input type="text" name="organizer_name" value="<?= htmlspecialchars($tournament['organizer_name']) ?>" class="readonly" readonly>
        <label>Organizer Contact:</label>
        <input type="text" name="organizer_contact" value="<?= htmlspecialchars($tournament['organizer_contact']) ?>" class="readonly" readonly>

        <label>Categories:</label>
        <div id="categoryList">
            <?php foreach ($categories as $index => $cat): ?>
                <div class="category-item">
                    <input type="hidden" name="categoryIDs[]" value="<?= htmlspecialchars($cat['ID']) ?>">
                    <input type="text" name="categories[]" value="<?= htmlspecialchars($cat['Category_Name']) ?>" required>
                    <input type="number" name="categoryFees[]" value="<?= htmlspecialchars($cat['Fee']) ?>" required min="0">
                    <button type="button" onclick="this.parentNode.remove()">Delete</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn" style="background-color:#007bff;" onclick="addCategoryField()">Add Category</button>

        <button type="submit" class="btn btn-save">Update Tournament</button>
        <button type="button" class="btn btn-cancel" onclick="window.location.href='../Styling/TournamentDetailS.php?id=<?= urlencode($tournamentID) ?>'">Cancel</button>

    </form>
</div>
<script>
    document.getElementById('posterInput').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('posterPreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    const input = document.getElementById("calendar");
    const today = new Date();
    const oneMonthLater = new Date();
    oneMonthLater.setMonth(today.getMonth() + 1);
    const minDate = oneMonthLater.toISOString().split("T")[0];
    input.min = minDate;

    function addCategoryField() {
        const div = document.createElement('div');
        div.className = 'category-item';
        div.innerHTML = `
            <input type="text" name="categories[]" placeholder="Category Name" required>
            <input type="number" name="categoryFees[]" placeholder="Fee (RM)" required min="0">
            <button type="button" onclick="this.parentNode.remove()">Delete</button>
        `;
        document.getElementById('categoryList').appendChild(div);
    }
</script>
</body>
</html>