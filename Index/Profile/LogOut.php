<?php
    session_name("profile");
    session_start();
    session_unset();
    session_destroy();
    header("Location: ../Homepage.php"); 
    exit();
?>