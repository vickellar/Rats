<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Clone with Styled Dropdown Menu</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        display: flex;
        height: 100vh;
        background-color: #eaeaea;
        transition: margin-left 0.3s ease;
        /* Smooth transition for content */
    }

    .sidebar {
        width: 200px;
        background-color: linear gradient(toright, rgb(5, 7, 113), hsl(168, 73.10%, 49.60%));
        /* Green background */
        color: white;
        padding: 20px;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        position: fixed;
        left: 0;
        /* Align to the left */
        top: 0;
        /* Align to the top */
        height: 100%;
        /* Full height */
        transform: translateX(-100%);
        /* Initially hidden */
        z-index: 1000;
        /* Ensure it appears above other content */
        transition: transform 0.3s ease;
        /* Smooth slide in/out */
    }

    .sidebar.active {
        transform: translateX(0);
        /* Slide in */
    }

    .main {
        flex: 1;
        padding: 20px;
        margin-left: 0;
        /* Initial margin */
        transition: margin-left 0.3s ease;
        /* Smooth transition for content */
    }

    .main.sidebar-active {
        margin-left: 250px;
        /* Push content to the right when sidebar is active */
        max-width: 350px;
        margin-right: 250px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 20px;
        border-bottom: 2px solid #eaeaea;
        position: relative;
        z-index: 1;
        /* Ensure header is above the sidebar */
    }

    .title {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        color: black;
        /* Black color */
        font-size: 28px;
        /* Increased font size */
        font-weight: bold;
    }

    .notification-icon {
        position: relative;
        font-size: 30px;
        color: #3498db;
        /* Blue color */
        cursor: pointer;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -10px;
        background-color: #e74c3c;
        color: white;
        border-radius: 50%;
        padding: 5px;
        font-size: 0.8em;
    }

    .hamburger {
        cursor: pointer;
        font-size: 30px;
        color: #2980b9;
    }

    .sidebar a {
        color: white;
        display: block;
        margin: 15px 0;
        /* Spacing between items */
        text-decoration: none;
        /* Remove underline */
    }

    .stats {
        display: flex;
        justify-content: space-between;
        margin: 20px 0;
    }

    .stat-card {
        background-color: #3498db;
        color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        flex: 1;
        margin: 0 10px;
    }

    .stat-card h3 {
        margin: 0 0 10px;
    }

    .received {
        margin-top: 20px;
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th,
    td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
    }

    /* Styles for the registration form */
    .registration-form {
        margin-top: 20px;
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .registration-form input {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        /* Spacing between inputs */
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .registration-form button {
        background-color: #3498db;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        width: 100%;
    }

    .registration-form button:hover {
        background-color: #2980b9;
    }
    </style>
</head>

<body>
    <div class="sidebar" id="sidebar">
        <h2>Menu</h2>
        <a href="#">Applications</a>
        <a href="#">Receipts</a>
        <a href="#">Reports</a>
        <a href="#">Downloads</a>
        <a href="#">Users</a>
        <a href="#">Settings</a>
        <a href="#">Logout</a>
    </div>

    <div class="main" id="main">
        <div class="header">
            <div class="hamburger" id="hamburger">&#9776;</div>
            <div class="title">FINANCIAL DIRECTOR</div>
            <div class="notification-icon" id="notificationIcon">&#128276;
                <span class="notification-badge" id="notificationCount">3</span>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Receipts</h3>
                <p>$12,000</p>
            </div>
            <div class="stat-card">
                <h3>Pending Approvals</h3>
                <p>5</p>
            </div>
            <div class="stat-card">
                <h3>New Users</h3>
                <p>10</p>
            </div>
        </div>

        <div class="received">
            <h2>Recent Activities</h2>
            <table>
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Receipt Submitted</td>
                        <td>01/01/2023</td>
                        <td>Approved</td>
                    </tr>
                    <tr>
                        <td>New User Registered</td>
                        <td>01/02/2023</td>
                        <td>Pending</td>
                    </tr>
                    <tr>
                        <td>Report Generated</td>
                        <td>01/03/2023</td>
                        <td>Completed</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- HTML Form for Lawyer Registration -->
        <div class="registration-form">
            <h2>upload bills</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="bill_id" value="1"> <!-- Set the Bill ID -->
                <input type="file" name="proof_of_payment" required>
                <button type="submit">Verify Payment</button>
            </form>
        </div>
    </div>

    <script>
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');

    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        main.classList.toggle('sidebar-active'); // Push main content to the right
    });

    // Simulating live notifications
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationCount = document.getElementById('notificationCount');

    // Example function to simulate receiving new notifications
    function receiveNotification() {
        let currentCount = parseInt(notificationCount.textContent);
        notificationCount.textContent = currentCount + 1;
    }

    // Simulate receiving a notification every 5 seconds
    setInterval(receiveNotification, 5000);
    </script>
</body>

</html>