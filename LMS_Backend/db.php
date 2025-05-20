<?php
    $conn = new mysqli("localhost", "root", "password", "libraryDB");
    if($conn->connect_error){
        http_response_code(500);
        die("Connection failed: " . $conn->connect_error);
    }
?>
