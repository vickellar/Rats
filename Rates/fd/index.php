<?php
// index.php
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centered Tiles</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }

        .container {
            display: flex;
            gap: 20px;
        }

        .tile {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 60px;
            width: 300px;
            text-align: center;
        }

        .tile h2 {
            margin: 0;
            font-size: 1.5em;
        }

        .tile p {
            margin: 10px 0 0;
        }
    </style>
</head>
<body>
    
    <div class="container">
        <div class="tile">
        <a href="cdashboard.php" style="text-decoration: none; color: inherit;"> 
            <h2></h2>
            <p>Customer Login</p>
        </div>
        <div class="tile">
        <a href="adLogin.php" style="text-decoration: none; color: inherit;"> 
            <h2></h2>
            <p>Admin Login</p>
        </div>
    </div>
</body>
</html>