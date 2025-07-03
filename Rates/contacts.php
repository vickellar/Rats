<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Contacts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('./assets/images/BACKGROUP.jpg') no-repeat center center fixed;
            background-size: 1300px;
            color: #333;
        }
        header {
            display: flex;
            align-items: center;
            background-color: rgba(36, 75, 184, 0.7);
            color: #fff;
            padding: 10px 20px;
        }
        header img {
            width: 100%;
            max-width: 150px;
            height: auto;
            margin-right: 5px;
        }
        header h1 {
            flex: 1;
            text-align: center;
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
            padding-bottom: 50px;
        }
        footer {
            background-color: rgba(65, 214, 182, 0.7);
            color: #fff;
            text-align: center;
            padding: 10px 0;
            position: relative;
            bottom: 0;
            width: 100%;
        }
        form {
            max-width: 600px;
            margin: auto;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 3px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: rgba(36, 75, 184, 0.7);
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background-color: rgba(31, 181, 192, 0.7);
        }
    </style>
</head>
<body>
    <header>
        <img src="./assets/images/mslogo.png" alt="Logo" />
        <h1>Rate Clearance System - Contacts</h1>
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
        <h2>Contact Us</h2>
        <form action="index.php?page=contacts" method="post">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required />
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required />
            
            <label for="message">Message:</label>
            <textarea id="message" name="message" rows="5" required></textarea>
            
            <button type="submit">Send</button>
        </form>
        <section style="margin-top: 30px;">
            <h3>Connect with us</h3>
            <ul style="list-style: none; padding: 0; display: flex; gap: 20px; align-items: center;">
                <li style="display: flex; align-items: center; gap: 5px;">
                    <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook" style="width: 20px; height: 20px;" />
                    <a href="https://www.facebook.com/YourOrganization" target="_blank" rel="noopener noreferrer">Facebook</a>
                </li>
                <li style="display: flex; align-items: center; gap: 5px;">
                    <img src="https://cdn-icons-png.flaticon.com/512/732/732200.png" alt="Email" style="width: 20px; height: 20px;" />
                    Email: <a href="mailto:info@masvingo.org.com">info@masvingo.org.com</a>
                </li>
                <li style="display: flex; align-items: center; gap: 5px;">
                    <img src="https://cdn-icons-png.flaticon.com/512/724/724664.png" alt="Phone" style="width: 20px; height: 20px;" />
                    Phone: <a href="tel:+1234567890">+1 (234) 567-890</a>
                </li>
            </ul>
        </section>
    </main>
    <?php include('./includes/footer.html'); ?>
</body>
</html>
