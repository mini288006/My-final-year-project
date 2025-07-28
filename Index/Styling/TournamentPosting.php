<!DOCTYPE html>
<html>
    <head>
        <style>
            body{
                display: flex;
                background-color: #0f4f8a;
                justify-content: center;
                align-items: center;
            }

            .form_container{
                align-items: left;
                background-color: #1c86f5;
                width: fit-content;
                height: fit-content;
                color: white;
                padding: 10px;
                border-radius: 10px;
                font-weight: bold;
            }
            
            .form{
                padding: 5px;
            }

            img{
                width: 700px;
                height: 300px;
                border-radius: 10px;
            }

            #imageUpload{
                display: none;
            }

            input,textarea,select{
                border-radius: 5px;
                border: inset #2e8b57 3px;
            }

            button{
                position: relative;
                top: 8px;
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

            #categoryListWrapper {
                margin-top: 20px;
                padding: 10px;
                background-color: #174e85;
                border-radius: 10px;
                max-width: 700px;
            }

            .category-display {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-top: 10px;
            }

            .category-item {
                background-color: #3b3f58;
                color: white;
                padding: 12px;
                border-radius: 10px;
                width: 45%;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            }

            .category-item button {
                margin-top: 8px;
                background-color: #e74c3c;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 6px;
                cursor: pointer;
                align-self: flex-end;
            }
        </style>
    </head>
    <body>
<?php
include_once '../../DATABASE/connectdb.php';
session_name("profile");
session_start();
$conn = connectdb();
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['User_tag']) || $_SESSION['User_tag'] !== 'O') {
        echo "<p style='color:red;'>You must be logged in as an organizer to post a tournament.</p>";
        exit;
    }

    if (!isset($_SESSION['ORGANIZER_ID'])) {
        echo "<p style='color:red;'>Organizer ID is missing from session. Please re-login.</p>";
        exit;
    }

    $organizerID = $_SESSION['ORGANIZER_ID'];

    $check = $conn->prepare("SELECT ORGANIZER_ID FROM organizers WHERE ORGANIZER_ID = ?");
    $check->bind_param("s", $organizerID);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows === 0) {
        echo "<p style='color:red;'>Organizer not found in the database.</p>";
        exit;
    }

    // === Poster Validation ===
    if (!isset($_FILES['poster']) || $_FILES['poster']['error'] !== 0) {
        $errors[] = "Poster upload failed or is missing.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['poster']['type'], $allowedTypes)) {
            $errors[] = "Only JPG, PNG, or WEBP images are allowed.";
        }
    }

    // === Initialize and Validate Inputs ===
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $organizerName = $_POST['organizer'] ?? '';
    $organizerContact = $_POST['contact'] ?? '';
    $sportType = "Badminton";

    // Title Validation
    if (mb_strlen($title) < 3 || !preg_match("/^[\p{L}0-9\s\-']+$/u", $title)) {
        $errors[] = "Invalid tournament title.";
    }

    // Description Validation
    if (mb_strlen(strip_tags($description)) < 10) {
        $errors[] = "Invalid tournament description. Must be at least 10 characters.";
    }

    // Date Validation
    if (!$date || strtotime($date) < strtotime("+1 month")) {
        $errors[] = "Tournament date must be at least 1 month from today.";
    }

    // Time Validation
    $validTimes = ["7:00 AM - 3:00 PM", "8:00 AM - 4:00 PM", "9:00 AM - 5:00 PM", "10:00 AM - 6:00 PM"];
    if (!in_array($time, $validTimes)) {
        $errors[] = "Please select a valid time slot.";
    }

    // Location Validation
    $locationPattern = "/^[\p{L}0-9\s.,'\"()\/\-–—‒‑@#&\r\n]{5,}$/u";
    if (!preg_match($locationPattern, $location)) {
        $errors[] = "Invalid location format.";
    }

    // Category Validation
    if (empty($_POST['categories'])) {
        $errors[] = "At least one category must be added.";
    } else {
        foreach ($_POST['categories'] as $cat) {
            if (!preg_match("/^[\p{L}0-9\s\-]{3,}$/u", $cat)) {
                $errors[] = "Invalid category: " . htmlspecialchars($cat);
            }
        }
    }

    if (!empty($_POST['categoryFees'])) {
        foreach ($_POST['categoryFees'] as $fee) {
            if (!is_numeric($fee) || $fee < 0) {
                $errors[] = "Each category fee must be a non-negative number.";
                break;
            }
        }
    }

    // === Insert If Valid ===
    if (empty($errors)) {
        // Save image
        $ext = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("poster_", true) . '.' . $ext;
        $uploadPath = "../../uploads/" . $uniqueName;
        move_uploaded_file($_FILES['poster']['tmp_name'], $uploadPath);

        // Insert tournament
        $stmt = $conn->prepare("INSERT INTO Tournament (TOURNAMENT_ID, ORGANIZER_ID, date, StartEndTime, location, Sport_Type, title, poster, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $tournamentID = uniqid("TID");
        $stmt->bind_param("sssssssss", $tournamentID, $organizerID, $date, $time, $location, $sportType, $title, $uniqueName, $description);

        if ($stmt->execute()) {
            // Insert categories
            if (!empty($_POST['categories']) && !empty($_POST['categoryFees'])) {
                $categoryStmt = $conn->prepare("INSERT INTO Tournament_Categories (ID, TOURNAMENT_ID, Category_Name, Fee) VALUES (?, ?, ?, ?)");
                for ($i = 0; $i < count($_POST['categories']); $i++) {
                    $categoryID = "CAT" . bin2hex(random_bytes(4));
                    $categoryName = $_POST['categories'][$i];
                    $categoryFee = $_POST['categoryFees'][$i];
                    $categoryStmt->bind_param("sssi", $categoryID, $tournamentID, $categoryName, $categoryFee);
                    $categoryStmt->execute();
                }
                $categoryStmt->close();
            }

            header("Location: ../dashboard.php");
            exit();
        } else {
            echo "<p style='color: red;'>Failed to post tournament: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }

    // Output Errors
    if (!empty($errors)) {
        echo "<ul style='color: red'>";
        foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
        echo "</ul>";
    }
}
?>
        <div class="form_container">
            <h1>Tournament posting page</h1>
            <h2>Host a Tournament!</h2>
            <form action="TournamentPosting.php" method="POST" enctype="multipart/form-data">
                <div class="form">
                    <label>Upload Tournament Poster</label><br>
                        <label for="imageUpload"><img src="../Images/TournamentPoster.jpg"></label>
                        <input type="file" id="imageUpload" accept="image/*" name="poster"><br><br>

                    <label>Title of Tournament</label><br>
                    <input type="text" style="width: 300px;" placeholder="e.g. Champions League" name="title"><br><br>

                    <label>Description</label><br>
                    <textarea id="description" style="width:300px; height:150px;" placeholder="e.g. Rules, Prizes, door gift" name="description"></textarea><br><br>

                    <label>Date of Tournament (1 month ahead of today)</label><br>
                    <input type="date" id="calendar" name="date"><br><br>

                    <label>Choose a time</label><br>
                    <select id="time" onchange="disablePlaceholder()" name="time" required>
                        <option value="">--</option>
                        <option value="7:00 AM - 3:00 PM">7:00 AM - 3:00 PM</option>
                        <option value="8:00 AM - 4:00 PM">8:00 AM - 4:00 PM</option>
                        <option value="9:00 AM - 5:00 PM">9:00 AM - 5:00 PM</option>
                        <option value="10:00 AM - 6:00 PM">10:00 AM - 6:00 PM</option>
                    </select> <br><br>

                    <label>Pick Location</label><br>
                    <textarea id="location" style="width: 300px; height:100px;" placeholder="e.g. Sungai besi Sports Younique" name="location"></textarea><br><br>

                    <label for="entryFee">Entry Fee</label><br>
                    <input type="number" id="entryFee" min="0" placeholder="Enter fee (in MYR)" name="entryFee"><br><br>

                    <label for="categoryInput">Add Category</label><br>
                    <input type="text" id="categoryInput" placeholder="e.g. U18 Man Single" name="categoryInput"><br>
                    <button type="button" onclick="addCategory()">Add</button>

                    <div id="categoryListWrapper">
                        <h3>Added Categories</h3>
                        <div id="categoryList" class="category-display"></div>
                    </div><br>

                    <button type="submit">Submit</button> 
                    <a href="../Homepage.php"><button type="button">Back to homepage</button></a>
                </div>
            </form>
        </div>
        <script>
            const input = document.getElementById("calendar");

            const today = new Date();
            const oneMonthLater = new Date();
            oneMonthLater.setMonth(today.getMonth() + 1);

            const minDate = oneMonthLater.toISOString().split("T")[0];
            input.min = minDate;

            function disablePlaceholder() {
                const select = document.getElementById("time");
                const placeholder = select.options[0];
                if (select.selectedIndex !== 0) {
                    placeholder.disabled = true;
                }
            }

            function addCategory() {
                const categoryInput = document.getElementById("categoryInput");
                const feeInput = document.getElementById("entryFee");
                const category = categoryInput.value.trim();
                const fee = feeInput.value.trim();
                const categoryList = document.getElementById("categoryList");

                if (category === "" || fee === "") return;

                const wrapper = document.createElement("div");
                wrapper.className = "category-item";

                const name = document.createElement("div");
                name.textContent = "Category: " + category;

                const feeDisplay = document.createElement("div");
                feeDisplay.textContent = "Fee: RM" + fee;

                // Hidden inputs to submit
                const hiddenCategory = document.createElement("input");
                hiddenCategory.type = "hidden";
                hiddenCategory.name = "categories[]";
                hiddenCategory.value = category;

                const hiddenFee = document.createElement("input");
                hiddenFee.type = "hidden";
                hiddenFee.name = "categoryFees[]";
                hiddenFee.value = fee;

                const btn = document.createElement("button");
                btn.textContent = "Delete";
                btn.onclick = () => wrapper.remove();

                wrapper.appendChild(name);
                wrapper.appendChild(feeDisplay);
                wrapper.appendChild(btn);
                wrapper.appendChild(hiddenCategory);
                wrapper.appendChild(hiddenFee);

                categoryList.appendChild(wrapper);

                categoryInput.value = "";
                feeInput.value = "";
            }

            document.getElementById('imageUpload').onchange = function (e) {
                const [file] = e.target.files;
                if (file) {
                    const preview = document.querySelector('label[for="imageUpload"] img');
                    preview.src = URL.createObjectURL(file);
                }
            };
        </script>   
    </body>
</html>