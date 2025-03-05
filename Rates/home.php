<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('./assets/images/BACKGROUP.jpg') no-repeat center center fixed; /* Replace with your image path */
            background-size: 1300px;
            color: #333;
        }
        header {
            display: flex; /* Use flexbox for layout */
            align-items: center; /* Vertically center items */
            background-color: rgba(36, 75, 184, 0.7);
            color: #fff;
            padding: 10px 20px; /* Add some padding */
        }
        header img {
            width: 100%;         /* Make the logo responsive */
            max-width: 150px;    /* Set a maximum width */
            height: auto;        /* Maintain aspect ratio */
            margin-right: 5px;  /* Reduce space between logo and title */
        }
        header h1 {
            flex: 1;             /* Allow title to take up remaining space */
            text-align: center;  /* Center the title */
        }
        nav {
            background-color: rgba(31, 181, 192, 0.7);
            color: #fff;
            padding: 10px 0;
            text-align: center;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
        }
        nav a:hover {
            background-color: rgb(189, 193, 199);
        }
        main {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding-bottom: 50px; /* Add space for footer */
        }
        footer {
            background-color: rgba(65, 214, 182, 0.7);
            color: #fff;
            text-align: center;
            padding: 10px 0;
            position: relative; /* Keep this as relative */
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <img src="./assets/images/mslogo.png" alt="Logo"> <!-- Add your logo -->
        <h1>Rate Clearance System</h1>
    </header>
    <nav>
        <a href="index.php?page=home">Home</a>
        <a href="index.php?page=login">Login</a>
        <a href="index.php?page=register">Register</a>
        <a href="index.php?page=services">Services</a>
        <a href="index.php?page=contacts">Contacts</a>
        <a href="index.php?page=about">About</a>
    </nav>
    <main>
    
    
       
    </main>
    <?php include('./includes/footer.html');?>
</body>
</html>