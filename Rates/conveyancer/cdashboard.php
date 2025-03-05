<?php
// dashboard.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Rates Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f9f9f9;
        }

        .header {
            background-color: blue;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 2em;
        }

        .container {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }

        .tile {
            background-color: lightblue;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin: 10px;
            width: 180px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .tile:hover {
            transform: scale(1.05);
        }

        .footer {
            margin: 0;
            background-color: red;
            color: white;
            text-align: center;
            padding: 10px;
            position: relative;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="header">
        Customer Rates Dashboard
    </div>

    <div class="container">
        <div class="tile" onclick="window.location.href='view_rate.php';">
            <h3>View Rate</h3>
        </div>
        <div class="tile" onclick="window.location.href='rate_applications.php';">
            <h3>Rate Applications</h3>
        </div>
        <div class="tile" onclick="window.location.href='view_history.php';">
            <h3>View History</h3>
        </div>
       
    </div>

   
</body>
</html>