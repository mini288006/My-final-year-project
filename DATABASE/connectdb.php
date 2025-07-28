<?php
function connectdb() {
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tournament_finder";
$conn = null;

try {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
} catch (Exception $e) {
    if(mysqli_connect_errno() == 1049) {
        //$conn = mysqli_connect($servername, $username, $password);
        //createDatabase($conn, $dbname);
        //$conn = mysqli_connect($servername, $username, $password, $dbname);
    }
}
if (!$conn) {
    die("Connection failed: " . mysqli_connect_errno());
}

return $conn;
}
?>